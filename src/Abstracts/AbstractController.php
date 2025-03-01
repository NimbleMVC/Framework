<?php

namespace NimblePHP\framework\Abstracts;

use Exception;
use NimblePHP\framework\Interfaces\ControllerInterface;
use NimblePHP\framework\Interfaces\RequestInterface;
use NimblePHP\framework\Interfaces\ResponseInterface;
use NimblePHP\framework\Log;
use NimblePHP\framework\Traits\LoadModelTrait;
use NimblePHP\framework\Attributes\Http\Action;

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
     */
    #[Action("disabled")]
    public function log(string $message, string $level = 'INFO', array $content = []): bool
    {
        return Log::log($message, $level, $content);
    }

    /**
     * After construct method
     * @return void
     */
    #[Action("disabled")]
    public function afterConstruct(): void
    {
    }

    /**
     * Magic get method
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    #[Action("disabled")]
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