<?php

namespace NimblePHP\framework\CLI\Attributes;

/**
 * Console command
 */
#[\Attribute]
class ConsoleCommand
{

    /**
     * Command
     * @var string 
     */
    public string $command;

    /**
     * Description
     * @var string
     */
    public string $description;

    /**
     * @param string $path
     * @param string $method
     */
    public function __construct(string $command, string $description)
    {
        $this->command = $command;
        $this->description = $description;
    }

}
