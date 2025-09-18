<?php

namespace NimblePHP\Framework\Abstracts;

use NimblePHP\Framework\Attributes\Http\Action;
use NimblePHP\Framework\DependencyInjector;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Exception\NotFoundException;
use NimblePHP\Framework\Interfaces\ControllerInterface;
use NimblePHP\Framework\Interfaces\RequestInterface;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Log;
use NimblePHP\Framework\Request;
use NimblePHP\Framework\Traits\LoadModelTrait;
use ReflectionException;
use ReflectionMethod;

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
     * Boot controller
     * @param string $action
     * @param array $params
     * @param bool $disableReflections
     * @return void
     * @throws NimbleException
     * @throws NotFoundException
     * @throws ReflectionException
     */
    #[Action("disabled")]
    public function boot(string $action, array $params = [], bool $disableReflections = false): void
    {
        $this->action = $action;
        $this->request = new Request();

        if (!$disableReflections) {
            $reflection = new ReflectionMethod($this, $action);
            $attributes = $reflection->getAttributes(Action::class);

            Kernel::$middlewareManager->runHook('afterAttributesController', [$reflection, $this]);

            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();

                if (method_exists($instance, 'handle')) {
                    $instance->handle($this, $action, $params);
                }
            }
        }

        $this->afterConstruct();
        DependencyInjector::inject($this);
    }

    /**
     * Run controller
     * @param string $action
     * @param array $params
     * @return void
     * @throws NimbleException
     * @throws NotFoundException
     * @throws ReflectionException
     */
    #[Action("disabled")]
    public function run(string $action, array $params = []): void
    {
        $this->boot($action, $params);
        call_user_func_array([$this, $action], $params);
        Kernel::$middlewareManager->runHook('afterController', [$this->name, $action, $params]);
    }

}