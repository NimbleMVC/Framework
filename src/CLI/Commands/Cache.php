<?php

namespace NimblePHP\framework\CLI\Commands;

use Krzysztofzylka\Console\Prints;
use NimblePHP\framework\CLI\Attributes\ConsoleCommand;
use NimblePHP\framework\Kernel;
use NimblePHP\framework\Route;
use NimblePHP\framework\Storage;

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
