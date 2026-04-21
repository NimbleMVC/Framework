<?php

namespace NimblePHP\Framework\CLI\Commands;

use NimblePHP\Framework\CLI\Attributes\ConsoleCommand;
use NimblePHP\Framework\CLI\Output;
use NimblePHP\Framework\Kernel;

/**
 *
 */
class Logs
{

    #[ConsoleCommand(
        'logs:clear',
        'Delete all logs files',
        help: 'Delete JSON log files from the project storage directory.',
        usage: 'php vendor/bin/nimble logs:clear',
        examples: [
            ['command' => 'php vendor/bin/nimble logs:clear', 'description' => 'Remove all stored log files.'],
        ]
    )]
    public function logsClear(Output $output): int
    {
        $logPath = Kernel::$projectPath . "/storage/logs/";

        if (!is_dir($logPath)) {
            $output->error('Not found /storage/logs');

            return 1;
        }

        foreach (glob($logPath . "*.log.json") as $file) {
            if (!unlink($file)) {
                $output->error('Failed delete log file');

                return 1;
            }
        }

        $output->success('Success');

        return 0;
    }

    #[ConsoleCommand(
        'logs:tail',
        'Live logs',
        help: 'Follow JSON log files in real time using tail -f.',
        usage: 'php vendor/bin/nimble logs:tail',
        examples: [
            ['command' => 'php vendor/bin/nimble logs:tail', 'description' => 'Stream new log entries live in the terminal.'],
        ]
    )]
    public function logTail(Output $output): int
    {
        $logFile = Kernel::$projectPath . "/storage/logs/*.log.json";

        if (empty(glob($logFile))) {
            $output->error('Not found logs');

            return 1;
        }

        $output->info('Live logs');
        $status = 0;
        passthru('tail -f ' . $logFile, $status);

        return $status;
    }

}
