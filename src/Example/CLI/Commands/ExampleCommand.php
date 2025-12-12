<?php

namespace NimblePHP\Framework\Example\CLI\Commands;

use Krzysztofzylka\Console\Prints;
use NimblePHP\Framework\CLI\Attributes\ConsoleCommand;

class ExampleCommand
{
    #[ConsoleCommand('example:hello', 'Example hello command')]
    public function hello(string $name = 'World'): void
    {
        Prints::print(value: "Hello, {$name}!", exit: true, color: 'green');
    }

    #[ConsoleCommand('example:info', 'Example info command')]
    public function info(): void
    {
        Prints::print(value: "This is an example command from module", exit: true, color: 'blue');
    }
}
