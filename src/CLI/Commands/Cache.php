<?php

namespace NimblePHP\Framework\CLI\Commands;

use Krzysztofzylka\Console\Prints;
use NimblePHP\Framework\CLI\Attributes\ConsoleCommand;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Routes\Route;

class Cache
{

    #[ConsoleCommand('cache:clear', 'Clear cache')]
    public function cacheClear(array $arguments = []): void
    {
        $cache = new \NimblePHP\Framework\Cache();
        $cache->delete(Route::$cacheKey);

        foreach (glob(Kernel::$projectPath . "/storage/session/sess_*") as $file) {
            unlink($file);
        }

        $exit = !isset($arguments['no-exit']);
        Prints::print(value: 'Cleared cache', exit: $exit, color: 'green');
    }

}
