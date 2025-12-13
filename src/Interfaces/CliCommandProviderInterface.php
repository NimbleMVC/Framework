<?php

namespace NimblePHP\Framework\Interfaces;

interface CliCommandProviderInterface
{
    /**
     * Get CLI command classes provided by this provider
     * @return array Array of command class instances
     */
    public function getCliCommands(): array;
}
