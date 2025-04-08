<?php

namespace NimblePHP\Framework\CLI\Commands;

use NimblePHP\Framework\CLI\Attributes\ConsoleCommand;
use NimblePHP\Framework\CLI\ConsoleHelper;

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