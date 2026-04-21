<?php

namespace NimblePHP\Framework\CLI\Commands;

use NimblePHP\Framework\CLI\AbstractCommand;
use NimblePHP\Framework\CLI\Attributes\ConsoleCommand;
use NimblePHP\Framework\Kernel;

/**
 * Serve development app
 */
#[ConsoleCommand(
    'serve',
    'Serve the application',
    help: 'Start the built-in PHP development server for the current project.',
    usage: 'php vendor/bin/nimble serve [host] [port]',
    arguments: [
        ['name' => 'host', 'description' => 'Host interface for the development server.', 'default' => '127.0.0.1'],
        ['name' => 'port', 'description' => 'Port used by the development server.', 'default' => '8080'],
    ],
    examples: [
        ['command' => 'php vendor/bin/nimble serve', 'description' => 'Start the server on the default host and port.'],
        ['command' => 'php vendor/bin/nimble serve 0.0.0.0 9000', 'description' => 'Expose the server on all interfaces and port 9000.'],
    ]
)]
class Serve extends AbstractCommand
{

    public function handle(): int
    {
        $host = (string)$this->argument('host', '127.0.0.1');
        $port = (int)$this->argument('port', 8080);

        $this->output()->info("Run server on http://$host:$port");
        chdir(Kernel::$projectPath);

        $status = 0;
        passthru(
            'php -S '
            . escapeshellarg($host . ':' . $port)
            . ' -t '
            . escapeshellarg(Kernel::$projectPath . '/public'),
            $status
        );

        return $status;
    }

}
