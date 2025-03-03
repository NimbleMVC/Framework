<?php

namespace NimblePHP\Framework\CLI\Commands;

use Krzysztofzylka\Console\Prints;
use NimblePHP\Framework\CLI\Attributes\ConsoleCommand;
use NimblePHP\Framework\Kernel;

/**
 *
 */
class Logs
{

    #[ConsoleCommand('logs:clear', 'Delete all logs files')]
    public function logsClear(): void
    {
        $logPath = Kernel::$projectPath . "/storage/logs/";

        if (!is_dir($logPath)) {
            Prints::print(value: "Not found /storage/logs", exit: true, color: 'red');
        }

        foreach (glob($logPath . "*.log.json") as $file) {
            if (!unlink($file)) {
                Prints::print(value: "Failed delete log file", exit: true, color: 'red');
            }
        }

        Prints::print(value: "Success", exit: true, color: 'green');
    }

    #[ConsoleCommand('logs:tail', 'Live logs')]
    public function logTail(): void
    {
        $logFile = Kernel::$projectPath . "/storage/logs/*.log.json";

        if (empty(glob($logFile))) {
            Prints::print(value: 'Not found logs', exit: true, color: 'red');
        }

        Prints::print('Live logs');
        passthru("tail -f " . $logFile);
    }

}
