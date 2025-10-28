<?php

namespace NimblePHP\Framework\CLI\Commands;

use Exception;
use Krzysztofzylka\Console\Prints;
use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use NimblePHP\Framework\CLI\Attributes\ConsoleCommand;
use NimblePHP\Framework\CLI\ConsoleHelper;
use NimblePHP\Framework\Exception\DatabaseException;
use NimblePHP\Framework\Exception\NimbleException;
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
    #[ConsoleCommand('cron:execute', 'Execute cron scripts')]
    public function execute(): void
    {
        ConsoleHelper::loadConfig();
        ConsoleHelper::initKernel();

        if (!$_ENV['DATABASE']) {
            Prints::print(value: 'Database must be enabled', exit: true, color: 'red');
        }

        $this->waitForDatabase();

        $cron = new \NimblePHP\Framework\Cron();
        $startTime = time();
        $maxDuration = 10 * 60;

        try {
            Prints::print(value: "Run jobs loop");

            do {
                $controller = empty($_ENV['CRON_CONTROLLER']) ? null : $_ENV['CRON_CONTROLLER'];
                $jobsRun = $cron->runJob($controller);

                if (!$jobsRun) {
                    sleep(5);
                } else {
                    usleep(200000);
                }
            } while ((time() - $startTime) < $maxDuration);

            Prints::print(value: "End run cron jobs", exit: true, color: 'green');
        } catch (DatabaseManagerException $exception) {
            Log::log('Cron error', 'ERR', ['exception' => $exception->getMessage(), 'trace' => $exception->getTraceAsString()]);
            Prints::print(value: "Cron error", exit: true, color: 'red');
        }
    }

    /**
     * @throws NimbleException
     * @throws Throwable
     * @throws DatabaseException
     */
    #[ConsoleCommand('cron:tasks', 'Add cron tasks')]
    public function tasks(): void
    {
        ConsoleHelper::loadConfig();
        ConsoleHelper::initKernel();

        if (!$_ENV['DATABASE']) {
            Prints::print(value: 'Database must be enabled', exit: true, color: 'red');
        }

        $this->waitForDatabase();

        $cron = new \NimblePHP\Framework\Cron();
        $cron->initTasks(Kernel::$projectPath . '/App/Model', '\App\Model');
    }

    /**
     * @return void
     */
    private function waitForDatabase(): void
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
            Prints::print(value: "Failed to connect to database after $maxAttempts attempts", exit: true, color: 'red');
        }
    }

}