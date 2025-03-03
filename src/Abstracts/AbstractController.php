<?php

namespace NimblePHP\Framework\Abstracts;

use Exception;
use NimblePHP\Framework\Interfaces\ControllerInterface;
use NimblePHP\Framework\Interfaces\RequestInterface;
use NimblePHP\Framework\Log;
use NimblePHP\Framework\Traits\LoadModelTrait;
use NimblePHP\Framework\Attributes\Http\Action;

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

}