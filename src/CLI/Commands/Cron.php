<?php

namespace NimblePHP\Framework\CLI\Commands;

use Exception;
use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use NimblePHP\Framework\CLI\Attributes\ConsoleCommand;
use NimblePHP\Framework\CLI\ConsoleHelper;
use NimblePHP\Framework\CLI\Output;
use NimblePHP\Framework\Config;
use NimblePHP\Framework\Exception\DatabaseException;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Interfaces\ControllerInterface;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Log;
use Throwable;

class Cron
{

    /**
     * @var bool
     */
    protected bool $shouldStopAfterCurrentIteration = false;

    /**
     * @throws NimbleException
     * @throws Throwable
     * @throws DatabaseException
     */
    #[ConsoleCommand(
        'cron:execute',
        'Execute cron scripts',
        help: 'Run queued cron jobs in a loop. By default the worker stops after 10 minutes, but it can also run continuously with safe limits. Use --workers to process multiple jobs concurrently in subprocesses.',
        usage: 'php vendor/bin/nimble cron:execute [--forever] [--max-duration=600] [--max-jobs=1000] [--max-memory-mb=128] [--workers=1]',
        options: [
            ['name' => '--forever', 'description' => 'Run the worker without a time limit.'],
            ['name' => '--max-duration', 'description' => 'Maximum worker lifetime in seconds. Use 0 to disable the time limit.'],
            ['name' => '--max-jobs', 'description' => 'Maximum number of executed jobs before graceful exit. Use 0 to disable the job limit.'],
            ['name' => '--max-memory-mb', 'description' => 'Maximum memory usage in MB before graceful exit. Use 0 to disable the memory limit.'],
            ['name' => '--workers', 'description' => 'Number of cron jobs to run concurrently in subprocesses. Default is 1 (no parallelism).'],
        ],
        examples: [
            ['command' => 'php vendor/bin/nimble cron:execute', 'description' => 'Start the cron worker loop.'],
            ['command' => 'php vendor/bin/nimble cron:execute --forever --max-memory-mb=128 --max-jobs=1000', 'description' => 'Run continuously but restart safely after memory or job thresholds.'],
            ['command' => 'php vendor/bin/nimble cron:execute --forever --workers=4', 'description' => 'Run continuously with 4 jobs processed in parallel subprocesses.'],
        ]
    )]
    public function execute(Output $output, array $options = []): int
    {
        ConsoleHelper::loadConfig();
        ConsoleHelper::initKernel();
        $this->registerSignalHandlers();

        if (!Config::get('DATABASE', false)) {
            $output->error('Database must be enabled');

            return 1;
        }

        if (!$this->waitForDatabase($output)) {
            return 1;
        }

        $workers = $this->resolvePositiveIntSetting($options, 'workers', 'CRON_WORKERS', 1);

        if ($workers > 1) {
            return $this->runSupervisor($output, $options, $workers);
        }

        return $this->runWorkerLoop($output, $options);
    }

    /**
     * @param Output $output
     * @param array $options
     * @return int
     * @throws NimbleException
     * @throws Throwable
     * @throws DatabaseException
     */
    protected function runWorkerLoop(Output $output, array $options): int
    {
        $cron = new \NimblePHP\Framework\Cron();
        $startTime = time();
        $workerLimits = $this->resolveWorkerLimits($options);
        $jobsProcessed = 0;

        try {
            $output->info('Run jobs loop');

            while (true) {
                $limitMessage = $this->resolveExitLimitMessage($startTime, $jobsProcessed, $workerLimits);

                if ($limitMessage !== null) {
                    $output->info($limitMessage);
                    break;
                }

                $controller = $this->resolveController(Config::get('CRON_CONTROLLER'));
                $jobsRun = $cron->runJob($controller, function (string $message) use ($output): void {
                    $output->info($message);
                });

                if ($jobsRun) {
                    $jobsProcessed++;
                    $this->collectGarbage();

                    $memoryLimitMessage = $this->resolveMemoryLimitMessage($workerLimits['maxMemoryBytes']);

                    if ($memoryLimitMessage !== null) {
                        $output->info($memoryLimitMessage);
                        break;
                    }
                }

                if ($this->shouldStopAfterCurrentIteration) {
                    $output->info('Stop signal received, exiting cron worker');
                    break;
                }

                if (!$jobsRun) {
                    $this->sleepInterruptibly(1);
                } else {
                    $this->sleepInterruptibly(0.2);
                }
            }

            $output->success('End run cron jobs');

            return 0;
        } catch (DatabaseManagerException $exception) {
            Log::log('Cron error', 'ERR', ['exception' => $exception->getMessage(), 'trace' => $exception->getTraceAsString()]);
            $output->error('Cron error');

            return 1;
        }
    }

    /**
     * Supervise a pool of worker subprocesses, each running its own cron job loop.
     * @param Output $output
     * @param array $options
     * @param int $workers
     * @return int
     * @throws NimbleException
     */
    protected function runSupervisor(Output $output, array $options, int $workers): int
    {
        if (!function_exists('proc_open')) {
            $output->error('Parallel cron workers require the proc_open function to be available.');

            return 1;
        }

        $command = $this->buildWorkerCommand($options);
        $output->info('Starting cron supervisor with ' . $workers . ' parallel worker(s)');

        $processes = [];

        for ($workerId = 1; $workerId <= $workers; $workerId++) {
            $processes[$workerId] = $this->spawnWorkerProcess($command, $workerId, $output);
        }

        while (!$this->shouldStopAfterCurrentIteration) {
            foreach ($processes as $workerId => $process) {
                if ($this->shouldStopAfterCurrentIteration) {
                    break;
                }

                $status = proc_get_status($process);

                if ($status['running']) {
                    continue;
                }

                proc_close($process);
                $output->info('Worker #' . $workerId . ' exited (exit code ' . $status['exitcode'] . '), restarting');
                $processes[$workerId] = $this->spawnWorkerProcess($command, $workerId, $output);
            }

            $this->sleepInterruptibly(1);
        }

        $output->info('Stop signal received, stopping worker processes');
        $this->stopWorkerProcesses($processes);
        $output->success('End run cron supervisor');

        return 0;
    }

    /**
     * Build the child worker command line, forwarding worker limit options and forcing a single job loop per process.
     * @param array $options
     * @return array
     */
    protected function buildWorkerCommand(array $options): array
    {
        $command = [PHP_BINARY, Kernel::$projectPath . '/vendor/bin/nimble', 'cron:execute'];

        foreach ($options as $name => $value) {
            if (is_int($name) || $name === 'workers') {
                continue;
            }

            $command[] = $value === true ? ('--' . $name) : ('--' . $name . '=' . $value);
        }

        $command[] = '--workers=1';

        return $command;
    }

    /**
     * @param array $command
     * @param int $workerId
     * @param Output $output
     * @return resource
     * @throws NimbleException
     */
    protected function spawnWorkerProcess(array $command, int $workerId, Output $output): mixed
    {
        $output->info('Starting worker #' . $workerId);

        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => STDOUT, 2 => STDERR], $pipes, Kernel::$projectPath);

        if ($process === false) {
            throw new NimbleException('Failed to start cron worker process #' . $workerId);
        }

        if (isset($pipes[0])) {
            fclose($pipes[0]);
        }

        return $process;
    }

    /**
     * @param array $processes
     * @return void
     */
    protected function stopWorkerProcesses(array $processes): void
    {
        foreach ($processes as $process) {
            $status = proc_get_status($process);

            if ($status['running']) {
                proc_terminate($process, defined('SIGTERM') ? SIGTERM : 15);
            }
        }

        foreach ($processes as $process) {
            proc_close($process);
        }
    }

    /**
     * @throws NimbleException
     * @throws Throwable
     * @throws DatabaseException
     */
    #[ConsoleCommand(
        'cron:tasks',
        'Add cron tasks',
        help: 'Scan project models and enqueue due cron tasks.',
        usage: 'php vendor/bin/nimble cron:tasks',
        examples: [
            ['command' => 'php vendor/bin/nimble cron:tasks', 'description' => 'Discover due cron tasks and add them to the queue.'],
        ]
    )]
    public function tasks(Output $output): int
    {
        ConsoleHelper::loadConfig();
        ConsoleHelper::initKernel();

        if (!Config::get('DATABASE', false)) {
            $output->error('Database must be enabled');

            return 1;
        }

        if (!$this->waitForDatabase($output)) {
            return 1;
        }

        $cron = new \NimblePHP\Framework\Cron();
        $cron->initTasks(Kernel::$projectPath . '/App/Model', '\App\Model');

        return 0;
    }

    /**
     * @param Output $output
     * @return bool
     */
    private function waitForDatabase(Output $output): bool
    {
        $connected = false;
        $attempts = 0;
        $maxAttempts = 30;

        while (!$connected && $attempts < $maxAttempts) {
            try {
                $connect = DatabaseConnect::create();

                switch (Config::get('DATABASE_TYPE')) {
                    case 'mysql':
                        $connect->setType(DatabaseType::mysql);
                        $connect->setHost(trim(Config::get('DATABASE_HOST')));
                        $connect->setDatabaseName(trim(Config::get('DATABASE_NAME')));
                        $connect->setUsername(trim(Config::get('DATABASE_USERNAME')));
                        $connect->setPassword(trim(Config::get('DATABASE_PASSWORD')));
                        $connect->setPort((int)Config::get('DATABASE_PORT', 3306));
                        break;
                    case 'sqlite':
                        $connect->setType(DatabaseType::sqlite);
                        $connect->setSqlitePath(Kernel::$projectPath . DIRECTORY_SEPARATOR . Config::get('DATABASE_PATH'));
                        break;
                    default:
                        throw new DatabaseException('Invalid database type');
                }

                $connect->connect(false);
                $connected = true;
            } catch (Exception) {
                $attempts++;
                echo "Wait for database connection... (attempt $attempts/$maxAttempts)" . PHP_EOL;
                sleep(2);
            }
        }

        if (!$connected) {
            $output->error("Failed to connect to database after $maxAttempts attempts");
        }

        return $connected;
    }

    /**
     * @param array $options
     * @return array{maxDuration:int,maxJobs:int,maxMemoryBytes:int}
     * @throws NimbleException
     */
    protected function resolveWorkerLimits(array $options): array
    {
        $maxDuration = $this->resolvePositiveIntSetting(
            $options,
            'max-duration',
            'CRON_MAX_DURATION',
            600
        );

        if ($this->isEnabledFlag($options, 'forever')) {
            $maxDuration = 0;
        }

        return [
            'maxDuration' => $maxDuration,
            'maxJobs' => $this->resolvePositiveIntSetting(
                $options,
                'max-jobs',
                'CRON_MAX_JOBS',
                0
            ),
            'maxMemoryBytes' => $this->resolvePositiveIntSetting(
                    $options,
                    'max-memory-mb',
                    'CRON_MAX_MEMORY_MB',
                    0
                ) * 1024 * 1024,
        ];
    }

    /**
     * @param int $startTime
     * @param int $jobsProcessed
     * @param array{maxDuration:int,maxJobs:int,maxMemoryBytes:int} $limits
     * @return string|null
     */
    protected function resolveExitLimitMessage(int $startTime, int $jobsProcessed, array $limits): ?string
    {
        if ($limits['maxDuration'] > 0 && (time() - $startTime) >= $limits['maxDuration']) {
            return 'Max duration reached, exiting cron worker';
        }

        if ($limits['maxJobs'] > 0 && $jobsProcessed >= $limits['maxJobs']) {
            return 'Max jobs reached, exiting cron worker';
        }

        return null;
    }

    /**
     * @param int $maxMemoryBytes
     * @return string|null
     */
    protected function resolveMemoryLimitMessage(int $maxMemoryBytes): ?string
    {
        if ($maxMemoryBytes <= 0) {
            return null;
        }

        if (memory_get_usage(true) >= $maxMemoryBytes) {
            return 'Max memory reached, exiting cron worker';
        }

        return null;
    }

    /**
     * @return void
     */
    protected function registerSignalHandlers(): void
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }

        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
        }

        if (defined('SIGTERM')) {
            pcntl_signal(SIGTERM, [$this, 'handleStopSignal']);
        }

        if (defined('SIGINT')) {
            pcntl_signal(SIGINT, [$this, 'handleStopSignal']);
        }
    }

    /**
     * @param int $signal
     * @return void
     */
    public function handleStopSignal(int $signal): void
    {
        $this->shouldStopAfterCurrentIteration = true;
    }

    /**
     * @param float $seconds
     * @return void
     */
    protected function sleepInterruptibly(float $seconds): void
    {
        $microsecondsRemaining = (int) round($seconds * 1000000);

        while ($microsecondsRemaining > 0 && !$this->shouldStopAfterCurrentIteration) {
            $chunk = min($microsecondsRemaining, 200000);
            usleep($chunk);
            $microsecondsRemaining -= $chunk;
        }
    }

    /**
     * @return void
     */
    protected function collectGarbage(): void
    {
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    /**
     * @param array $options
     * @param string $optionName
     * @return bool
     */
    protected function isEnabledFlag(array $options, string $optionName): bool
    {
        if (!array_key_exists($optionName, $options)) {
            return false;
        }

        $value = $options[$optionName];

        if (is_bool($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return true;
        }

        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $normalized ?? true;
    }

    /**
     * @param array $options
     * @param string $optionName
     * @param string $configKey
     * @param int $default
     * @return int
     * @throws NimbleException
     */
    protected function resolvePositiveIntSetting(array $options, string $optionName, string $configKey, int $default): int
    {
        $value = $options[$optionName] ?? Config::get($configKey, $default);

        if ($value === null || $value === '') {
            return $default;
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 0) {
            throw new NimbleException('Invalid value for ' . $optionName . '. Expected a non-negative integer.');
        }

        return (int) $value;
    }

    /**
     * Resolve configured cron controller.
     * @param mixed $controller
     * @return ControllerInterface|null
     * @throws NimbleException
     */
    private function resolveController(mixed $controller): ?ControllerInterface
    {
        if ($controller === null || $controller === '') {
            return null;
        }

        if ($controller instanceof ControllerInterface) {
            return $controller;
        }

        if (!is_string($controller)) {
            throw new NimbleException('CRON_CONTROLLER must be null, a controller instance or a controller class-string');
        }

        $controllerClass = ltrim(trim($controller), '\\');

        if ($controllerClass === '') {
            return null;
        }

        if (!class_exists($controllerClass)) {
            throw new NimbleException('CRON_CONTROLLER class "' . $controllerClass . '" does not exist');
        }

        if (!is_subclass_of($controllerClass, ControllerInterface::class)) {
            throw new NimbleException('CRON_CONTROLLER class "' . $controllerClass . '" must implement ' . ControllerInterface::class);
        }

        /** @var ControllerInterface $controllerInstance */
        $controllerInstance = new $controllerClass();
        $controllerInstance->afterConstruct();

        return $controllerInstance;
    }

}
