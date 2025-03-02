<?php

namespace NimblePHP\framework\CLI\Commands;

use NimblePHP\framework\Kernel;

class Serve
{

    public function handle(string $host = '127.0.0.1', int $port = 8080): void
    {
        echo "Run server on http://$host:$port\n";
        exec("cd " . Kernel::$projectPath ." && php -S $host:$port -t public");
    }

}