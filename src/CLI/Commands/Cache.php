<?php

namespace NimblePHP\Framework\CLI\Commands;

use Krzysztofzylka\Console\Prints;
use NimblePHP\Framework\CLI\Attributes\ConsoleCommand;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Routes\Route;
use NimblePHP\Framework\Storage;

class Cache
{

    #[ConsoleCommand('cache:clear', 'Clear cache')]
    public function cacheClear(): void
    {
        $storage = new Storage('cache');
        $storage->delete(Route::$cacheFile);

        foreach (glob(Kernel::$projectPath . "/storage/session/sess_*") as $file) {
            unlink($file);
        }

        Prints::print(value: 'Cleared cache', exit: true, color: 'green');
    }

}
