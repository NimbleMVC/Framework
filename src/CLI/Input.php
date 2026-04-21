<?php

namespace NimblePHP\Framework\CLI;

class Input
{

    /**
     * @param string $command
     * @param array $rawArguments
     * @param array $parsedArguments
     * @param array $argumentMetadata
     */
    public function __construct(
        private readonly string $command,
        private readonly array $rawArguments,
        private readonly array $parsedArguments,
        array $argumentMetadata = []
    )
    {
        $this->positionals = array_values(array_filter(
            $parsedArguments,
            static fn ($key) => is_int($key),
            ARRAY_FILTER_USE_KEY
        ));

        $this->options = array_filter(
            $parsedArguments,
            static fn ($key) => !is_int($key),
            ARRAY_FILTER_USE_KEY
        );

        $this->arguments = [];

        foreach (array_values($argumentMetadata) as $index => $argument) {
            if (!array_key_exists($index, $this->positionals)) {
                continue;
            }

            $name = $argument['name'] ?? (string)$index;
            $this->arguments[$name] = $this->positionals[$index];
        }
    }

    /**
     * Positional arguments indexed by order.
     * @var array
     */
    private array $positionals = [];

    /**
     * Named options.
     * @var array
     */
    private array $options = [];

    /**
     * Named arguments resolved from metadata.
     * @var array
     */
    private array $arguments = [];

    /**
     * @return string
     */
    public function command(): string
    {
        return $this->command;
    }

    /**
     * @return array
     */
    public function rawArguments(): array
    {
        return $this->rawArguments;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->parsedArguments;
    }

    /**
     * @return array
     */
    public function positionals(): array
    {
        return $this->positionals;
    }

    /**
     * @return array
     */
    public function arguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return array
     */
    public function options(): array
    {
        return $this->options;
    }

    /**
     * @param string|int $name
     * @param mixed $default
     * @return mixed
     */
    public function argument(string|int $name, mixed $default = null): mixed
    {
        if (is_int($name)) {
            return $this->positionals[$name] ?? $default;
        }

        return $this->arguments[$name] ?? $default;
    }

    /**
     * @param string|int $name
     * @return bool
     */
    public function hasArgument(string|int $name): bool
    {
        if (is_int($name)) {
            return array_key_exists($name, $this->positionals);
        }

        return array_key_exists($name, $this->arguments);
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function option(string $name, mixed $default = null): mixed
    {
        $normalizedName = $this->normalizeOptionName($name);

        return $this->options[$normalizedName] ?? $default;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasOption(string $name): bool
    {
        return array_key_exists($this->normalizeOptionName($name), $this->options);
    }

    /**
     * @param string $name
     * @return string
     */
    private function normalizeOptionName(string $name): string
    {
        return ltrim(trim($name), '-');
    }

}
