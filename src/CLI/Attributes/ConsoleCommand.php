<?php

namespace NimblePHP\Framework\CLI\Attributes;

use Attribute;

/**
 * Console command
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
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
     * Detailed description shown in command help.
     * @var string|null
     */
    public ?string $help;

    /**
     * Usage line shown in command help.
     * @var string|null
     */
    public ?string $usage;

    /**
     * Argument metadata.
     * @var array
     */
    public array $arguments;

    /**
     * Option metadata.
     * @var array
     */
    public array $options;

    /**
     * Example metadata.
     * @var array
     */
    public array $examples;

    /**
     * @param string $command
     * @param string $description
     * @param string|null $help
     * @param string|null $usage
     * @param array $arguments
     * @param array $options
     * @param array $examples
     */
    public function __construct(
        string $command,
        string $description,
        ?string $help = null,
        ?string $usage = null,
        array $arguments = [],
        array $options = [],
        array $examples = []
    )
    {
        $this->command = $command;
        $this->description = $description;
        $this->help = $help;
        $this->usage = $usage;
        $this->arguments = $arguments;
        $this->options = $options;
        $this->examples = $examples;
    }

}
