<?php

namespace NimblePHP\Framework\Example;

use NimblePHP\Framework\Example\CLI\Commands\ExampleCommand;
use NimblePHP\Framework\Interfaces\CliCommandProviderInterface;
use NimblePHP\Framework\Interfaces\ServiceProviderInterface;

/**
 * Example service provider with CLI commands
 */
class ExampleServiceProvider implements ServiceProviderInterface, CliCommandProviderInterface
{
    /**
     * Register services
     * @return void
     */
    public function register(): void
    {
        // Service registration logic here
    }

    /**
     * Get CLI command classes provided by this provider
     * @return array
     */
    public function getCliCommands(): array
    {
        return [
            new ExampleCommand(),
        ];
    }
}
