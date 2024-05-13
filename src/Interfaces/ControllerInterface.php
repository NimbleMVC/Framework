<?php

namespace Nimblephp\framework\Interfaces;

use Nimblephp\framework\Abstracts\AbstractModel;
use Nimblephp\framework\Exception\NimbleException;
use Nimblephp\framework\Exception\NotFoundException;
use Nimblephp\framework\Log;

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