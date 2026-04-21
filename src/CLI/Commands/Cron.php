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
     * @throws NimbleException
     * @throws Throwable
     * @throws DatabaseException
     */
    #[ConsoleCommand(
        'cron:execute',
        'Execute cron scripts',
        help: 'Run queued cron jobs in a loop for up to 10 minutes.',
        usage: 'php vendor/bin/nimble cron:execute',
        examples: [
            ['command' => 'php vendor/bin/nimble cron:execute', 'description' => 'Start the cron worker loop.'],
        ]
    )]
    public function execute(Output $output): int
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
        $startTime = time();
        $maxDuration = 10 * 60;

        try {
            $output->info('Run jobs loop');

            do {
                $controller = $this->resolveController(Config::get('CRON_CONTROLLER', null));
                $jobsRun = $cron->runJob($controller);

                if (!$jobsRun) {
                    sleep(5);
                } else {
                    usleep(200000);
                }
            } while ((time() - $startTime) < $maxDuration);

            $output->success('End run cron jobs');

            return 0;
        } catch (DatabaseManagerException $exception) {
            Log::log('Cron error', 'ERR', ['exception' => $exception->getMessage(), 'trace' => $exception->getTraceAsString()]);
            $output->error('Cron error');

            return 1;
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

        if (!$_ENV['DATABASE']) {
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

                switch ($_ENV['DATABASE_TYPE']) {
                    case 'mysql':
                        $connect->setType(DatabaseType::mysql);
                        $connect->setHost(trim($_ENV['DATABASE_HOST']));
                        $connect->setDatabaseName(trim($_ENV['DATABASE_NAME']));
                        $connect->setUsername(trim($_ENV['DATABASE_USERNAME']));
                        $connect->setPassword(trim($_ENV['DATABASE_PASSWORD']));
                        $connect->setPort((int)$_ENV['DATABASE_PORT']);
                        break;
                    case 'sqlite':
                        $connect->setType(DatabaseType::sqlite);
                        $connect->setSqlitePath(Kernel::$projectPath . DIRECTORY_SEPARATOR . $_ENV['DATABASE_PATH']);
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
