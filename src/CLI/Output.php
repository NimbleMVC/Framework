<?php

namespace NimblePHP\Framework\CLI;

use Krzysztofzylka\Console\Generator\Table;
use Krzysztofzylka\Console\Prints;

class Output
{

    /**
     * @param string $message
     * @return void
     */
    public function write(string $message): void
    {
        Prints::write($message);
    }

    /**
     * @param string $message
     * @return void
     */
    public function line(string $message = ''): void
    {
        Prints::line($message);
    }

    /**
     * @param string $message
     * @return void
     */
    public function info(string $message): void
    {
        Prints::info($message);
    }

    /**
     * @param string $message
     * @return void
     */
    public function success(string $message): void
    {
        Prints::success($message);
    }

    /**
     * @param string $message
     * @return void
     */
    public function warning(string $message): void
    {
        Prints::warning($message);
    }

    /**
     * @param string $message
     * @return void
     */
    public function error(string $message): void
    {
        Prints::error($message);
    }

    /**
     * @param string $title
     * @param string|null $description
     * @return void
     */
    public function section(string $title, ?string $description = null): void
    {
        Prints::section($title, $description);
    }

    /**
     * @param array $items
     * @return void
     */
    public function bulletList(array $items): void
    {
        Prints::bulletList($items);
    }

    /**
     * @param array $items
     * @return void
     */
    public function kv(array $items): void
    {
        Prints::kv($items);
    }

    /**
     * @param mixed $data
     * @return void
     */
    public function json(mixed $data): void
    {
        Prints::json($data);
    }

    /**
     * @param Table $table
     * @return void
     */
    public function table(Table $table): void
    {
        $table->render();
    }

}
