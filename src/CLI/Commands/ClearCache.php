<?php

namespace NimblePHP\framework\CLI\Commands;

use Krzysztofzylka\Console\Prints;
use NimblePHP\framework\Route;
use NimblePHP\framework\Storage;

class ClearCache
{

    public static string $description = 'Clear cache';

    /**
     * Clear cache
     * @return void
     */
    public function handle(): void
    {
        $storage = new Storage('cache');
        $storage->delete(Route::$cacheFile);
        Prints::print(value: 'Cleared cache', exit: true, color: 'green');
    }

}
