<?php

namespace NimblePHP\framework\CLI\Commands;

use NimblePHP\framework\Route;
use NimblePHP\framework\Storage;

class ClearCache
{

    /**
     * Clear cache
     * @return void
     */
    public function handle(): void
    {
        $storage = new Storage('cache');
        $storage->delete(Route::$cacheFile);
        echo "Clear cache!\n";
    }

}
