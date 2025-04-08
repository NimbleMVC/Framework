<?php

namespace NimblePHP\Framework\CLI\Attributes;

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
     * @param string $command
     * @param string $description
     */
    public function __construct(string $command, string $description)
    {
        $this->command = $command;
        $this->description = $description;
    }

}
