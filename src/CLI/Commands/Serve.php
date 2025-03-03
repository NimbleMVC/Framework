<?php

namespace NimblePHP\framework\CLI\Commands;

use NimblePHP\framework\CLI\Attributes\ConsoleCommand;
use NimblePHP\framework\Kernel;

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