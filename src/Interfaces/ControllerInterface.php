<?php

namespace NimblePHP\Framework\Interfaces;

use NimblePHP\Framework\Abstracts\AbstractModel;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Exception\NotFoundException;

interface ControllerInterface
{

    /**
     * Load model
     * @template T
     * @param class-string<T> $name
     * @return T
     * @throws NimbleException
     * @throws NotFoundException
     */
    public function loadModel(string $name): object;

    /**
     * Create log
     * @param string $message
     * @param string $level
     * @param array $content
     * @return bool
     */
    public function log(string $message, string $level = 'INFO', array $content = []): bool;

    /**
     * After construct method
     * @return void
     */
    public function afterConstruct(): void;

}