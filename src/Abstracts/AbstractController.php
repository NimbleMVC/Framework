<?php

namespace Nimblephp\framework\Abstracts;

use Exception;
use Krzysztofzylka\Generator\Generator;
use Nimblephp\debugbar\Debugbar;
use Nimblephp\framework\Exception\NimbleException;
use Nimblephp\framework\Exception\NotFoundException;
use Nimblephp\framework\Interfaces\ControllerInterface;
use Nimblephp\framework\Interfaces\RequestInterface;
use Nimblephp\framework\Interfaces\ResponseInterface;
use Nimblephp\framework\Kernel;
use Nimblephp\framework\Log;
use Nimblephp\framework\Traits\LoadModelTrait;

/**
 * Abstract controller
 */
abstract class AbstractController implements ControllerInterface
{

    use LoadModelTrait;

    /**
     * Controller name
     * @var string
     */
    public string $name;

    /**
     * Controller action
     * @var string
     */
    public string $action;

    /**
     * Response instance
     * @var ResponseInterface
     */
    public ResponseInterface $response;

    /**
     * Request instance
     * @var RequestInterface
     */
    public RequestInterface $request;

    /**
     * Create logs
     * @param string $message
     * @param string $level
     * @param array $content
     * @return bool
     * @throws Exception
     * @action disabled
     */
    public function log(string $message, string $level = 'INFO', array $content = []): bool
    {
        return Log::log($message, $level, $content);
    }

    /**
     * After construct method
     * @return void
     * @action disabled
     */
    public function afterConstruct(): void
    {
    }

    /**
     * Magic get method
     * @param string $name
     * @return mixed
     * @throws Exception
     * @action disabled
     */
    public function __get(string $name)
    {
        $loadModel = $this->__getModel($name);

        if (!is_null($loadModel)) {
            return $loadModel;
        }

        $className = $this::class;

        throw new Exception("Undefined property: {$className}::{$name}", 2);
    }

}