<?php

namespace NimblePHP\Framework\CLI;

abstract class AbstractCommand
{

    private ?Input $input = null;

    private ?Output $output = null;

    /**
     * @param Input $input
     * @param Output $output
     * @return int
     */
    final public function run(Input $input, Output $output): int
    {
        $this->input = $input;
        $this->output = $output;

        return $this->handle();
    }

    /**
     * @return int
     */
    abstract public function handle(): int;

    /**
     * @return Input
     */
    protected function input(): Input
    {
        return $this->input ?? new Input('', [], [], []);
    }

    /**
     * @return Output
     */
    protected function output(): Output
    {
        return $this->output ?? new Output();
    }

    /**
     * @param string|int $name
     * @param mixed $default
     * @return mixed
     */
    protected function argument(string|int $name, mixed $default = null): mixed
    {
        return $this->input()->argument($name, $default);
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    protected function option(string $name, mixed $default = null): mixed
    {
        return $this->input()->option($name, $default);
    }

    /**
     * @param string $name
     * @return bool
     */
    protected function hasOption(string $name): bool
    {
        return $this->input()->hasOption($name);
    }

}
