<?php

namespace NimblePHP\Framework\CLI\Commands;

use Krzysztofzylka\Console\Generator\Table;
use NimblePHP\Framework\CLI\Attributes\ConsoleCommand;
use NimblePHP\Framework\CLI\ConsoleHelper;
use NimblePHP\Framework\CLI\Output;
use NimblePHP\Framework\Exception\DatabaseException;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Routes\Route;
use Throwable;

class Routes
{

    /**
     * @return void
     * @throws DatabaseException
     * @throws Throwable
     */
    #[ConsoleCommand(
        'routes:list',
        'List routes',
        help: 'Boot the project kernel and display registered application routes.',
        usage: 'php vendor/bin/nimble routes:list',
        examples: [
            ['command' => 'php vendor/bin/nimble routes:list', 'description' => 'Print the registered routes table.'],
        ]
    )]
    public function routesList(Output $output): int
    {
        ConsoleHelper::initKernel();

        $table = new Table();
        $table->addColumn('Route', 'path');
        $table->addColumn('Controller', 'controller');
        $table->addColumn('Method', 'method');
        $table->addColumn('HTTP', 'httpMethod');
        $table->setData(Route::getRoutes());
        $output->table($table);

        return 0;
    }

    /**
     * @return void
     * @throws DatabaseException
     * @throws Throwable
     * @throws NimbleException
     */
    #[ConsoleCommand(
        'routes:generate',
        'Generate routes',
        help: 'Generate the cached routes file for the current project.',
        usage: 'php vendor/bin/nimble routes:generate',
        examples: [
            ['command' => 'php vendor/bin/nimble routes:generate', 'description' => 'Rebuild the routes cache file.'],
        ]
    )]
    public function routesGenerate(Output $output): int
    {
        ConsoleHelper::initKernel();

        \NimblePHP\Framework\Config::set('CACHE_ROUTE', true);
        Route::registerRoutes(Kernel::$projectPath . '/App/Controller', 'App\Controller');
        $output->success('Successfully generated routes file.');

        return 0;
    }

}
