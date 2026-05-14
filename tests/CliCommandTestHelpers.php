<?php

use NimblePHP\Framework\CLI\Output;

class RecordingOutput extends Output
{
    public array $writes = [];

    public array $lines = [];

    public array $infos = [];

    public array $successes = [];

    public array $warnings = [];

    public array $errors = [];

    public array $sections = [];

    public array $bulletLists = [];

    public array $kvPayloads = [];

    public array $jsonPayloads = [];

    public array $tables = [];

    public function write(string $message): void
    {
        $this->writes[] = $message;
    }

    public function line(string $message = ''): void
    {
        $this->lines[] = $message;
    }

    public function info(string $message): void
    {
        $this->infos[] = $message;
    }

    public function success(string $message): void
    {
        $this->successes[] = $message;
    }

    public function warning(string $message): void
    {
        $this->warnings[] = $message;
    }

    public function error(string $message): void
    {
        $this->errors[] = $message;
    }

    public function section(string $title, ?string $description = null): void
    {
        $this->sections[] = [$title, $description];
    }

    public function bulletList(array $items): void
    {
        $this->bulletLists[] = $items;
    }

    public function kv(array $items): void
    {
        $this->kvPayloads[] = $items;
    }

    public function json(mixed $data): void
    {
        $this->jsonPayloads[] = $data;
    }

    public function table(\Krzysztofzylka\Console\Generator\Table $table): void
    {
        $this->tables[] = $table;
    }
}
