<?php

namespace NimblePHP\framework\CLI\Commands;

use Exception;
use Krzysztofzylka\Env\Env;
use NimblePHP\framework\CLI\Attributes\ConsoleCommand;
use NimblePHP\framework\CLI\ConsoleHelper;
use NimblePHP\framework\Kernel;

class Config
{

    #[ConsoleCommand('config:show', 'Show configuration')]
    public function configShow(): void
    {
        ConsoleHelper::loadConfig();

        foreach ($_ENV as $name => $value) {
            echo "$name: " . (is_bool($value) ? ($value ? 'True' : 'False') : $value) . PHP_EOL;
        }
    }

}