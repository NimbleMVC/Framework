<?php

namespace NimblePHP\Framework\CLI\Commands;

use NimblePHP\Framework\CLI\AbstractCommand;
use NimblePHP\Framework\CLI\Attributes\ConsoleCommand;
use NimblePHP\Framework\CLI\ConsoleHelper;

#[ConsoleCommand(
    'config:show',
    'Show configuration',
    help: 'Load the framework configuration and print environment values.',
    usage: 'php vendor/bin/nimble config:show',
    examples: [
        ['command' => 'php vendor/bin/nimble config:show', 'description' => 'Display the current configuration variables.'],
    ]
)]
class Config extends AbstractCommand
{

    public function handle(): int
    {
        ConsoleHelper::loadConfig();
        $config = [];

        foreach ($_ENV as $name => $value) {
            $config[$name] = is_bool($value) ? ($value ? 'True' : 'False') : (string)$value;
        }

        $this->output()->kv($config);

        return 0;
    }

}
