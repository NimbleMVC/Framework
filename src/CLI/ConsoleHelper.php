<?php

namespace NimblePHP\framework\CLI;

use NimblePHP\framework\Kernel;

class ConsoleHelper
{


    /**
     * Init project path for methods
     * @return void
     */
    public static function initProjectPath(): void
    {
        Kernel::$projectPath = getcwd();
    }

}