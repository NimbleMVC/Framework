<?php

namespace NimblePHP\framework\Interfaces;

use NimblePHP\framework\Abstracts\AbstractModel;
use NimblePHP\framework\Exception\NimbleException;
use NimblePHP\framework\Exception\NotFoundException;

interface ControllerInterface
{

    /**
     * Load model
     * @param string $name
     * @return AbstractModel
     * @throws NimbleException
     * @throws NotFoundException
     */
    public function loadModel(string $name): AbstractModel;

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