<?php

namespace NimblePHP\Framework\CLI\Commands;

use NimblePHP\Framework\CLI\Attributes\ConsoleCommand;
use NimblePHP\Framework\CLI\Output;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Routes\Route;

class Cache
{

    #[ConsoleCommand(
        'cache:clear',
        'Clear cache',
        help: 'Clear cached routes and session files for the current project.',
        usage: 'php vendor/bin/nimble cache:clear [--no-exit]',
        options: [
            ['name' => '--no-exit', 'description' => 'Do not terminate the script after clearing cache.'],
        ],
        examples: [
            ['command' => 'php vendor/bin/nimble cache:clear', 'description' => 'Clear the application cache and exit.'],
        ]
    )]
    public function cacheClear(array $arguments = [], ?Output $output = null): int
    {
        $output ??= new Output();

        $cache = new \NimblePHP\Framework\Cache();
        $cache->delete(Route::$cacheKey);

        foreach (glob(Kernel::$projectPath . "/storage/session/sess_*") as $file) {
            unlink($file);
        }

        $exit = !isset($arguments['no-exit']);
        $output->success('Cleared cache');

        return $exit ? 0 : 0;
    }

}
