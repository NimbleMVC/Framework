<?php

namespace NimblePHP\framework\CLI\Commands;

use Krzysztofzylka\Console\Generator\Table;
use Krzysztofzylka\Console\Prints;
use Krzysztofzylka\Env\Env;
use NimblePHP\framework\CLI\Attributes\ConsoleCommand;
use NimblePHP\framework\CLI\ConsoleHelper;
use NimblePHP\framework\Kernel;
use NimblePHP\framework\Route;
use NimblePHP\framework\Storage;

class Routes
{

    #[ConsoleCommand('routes:list', 'List routes')]
    public function routesList(): void
    {
        ConsoleHelper::initKernel();
        
        $table = new Table();
        $table->addColumn('Route', 'route');
        $table->addColumn('Controller', 'controller');
        $table->addColumn('Method', 'method');
        $table->setData(Route::getRoutes());
        $table->render();
    }

    #[ConsoleCommand('routes:generate', 'Generate routes')]
    public function routesGenerate(): void
    {
        ConsoleHelper::initKernel();

        $_ENV['CACHE_ROUTE'] = true;
        Route::registerRoutes(Kernel::$projectPath . '/App/Controller', 'App\Controller');
        Prints::print('Successfully generated routes file.', color: 'green');
    }

}
