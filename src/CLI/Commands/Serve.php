<?php

namespace NimblePHP\framework\CLI\Commands;

use NimblePHP\framework\Kernel;

/**
 * Serve development app
 */
class Serve
{

    public static string $description = 'Serve the application';

    /**
     * Serve
     * @param string $host
     * @param int $port
     * @return void
     */
    public function handle(string $host = '127.0.0.1', int $port = 8080): void
    {
        echo "Run server on http://$host:$port\n";
        exec("cd " . Kernel::$projectPath ." && php -S $host:$port -t public");
    }

}