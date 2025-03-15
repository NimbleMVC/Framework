<?php

namespace NimblePHP\Framework\CLI\Commands;

use NimblePHP\Framework\CLI\Attributes\ConsoleCommand;
use NimblePHP\Framework\Kernel;

/**
 * Serve development app
 */
class Serve
{

    #[ConsoleCommand('serve', 'Serve the application')]
    public function handle(string $host = '127.0.0.1', int $port = 8080): void
    {
        echo "Run server on http://$host:$port\n";
        exec("cd " . Kernel::$projectPath . " && php -S $host:$port -t public");
    }

}