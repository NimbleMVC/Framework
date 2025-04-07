<?php

namespace NimblePHP\Framework\CLI\Commands;

use Krzysztofzylka\Console\Prints;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use NimblePHP\Framework\CLI\Attributes\ConsoleCommand;
use NimblePHP\Framework\CLI\ConsoleHelper;
use NimblePHP\Framework\Exception\DatabaseException;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Log;
use PDO;
use PDOException;
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
                $cron->runJob();
                sleep(1);
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

        while (!$connected) {
            try {
                $pdo = new PDO('mysql:host=' . $_ENV['DATABASE_HOST'] . ';port=' . $_ENV['DATABASE_PORT'], $_ENV['DATABASE_USERNAME'], $_ENV['DATABASE_PASSWORD']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $connected = true;
                $pdo = null;
            } catch (PDOException $e) {
                echo "Wait for database connection..." . PHP_EOL;
                sleep(1);
            }
        }
    }

}
