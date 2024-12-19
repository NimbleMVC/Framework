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

/**
 * Abstract controller
 */
abstract class AbstractController implements ControllerInterface
{

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
     * Models list
     * @var array
     */
    public array $models = [];

    /**
     * Load model
     * @param string $name
     * @return AbstractModel
     * @throws NimbleException
     * @throws NotFoundException
     * @action disabled
     */
    public function loadModel(string $name): AbstractModel
    {
        if (Kernel::$activeDebugbar) {
            try {
                $debugbarUid = Debugbar::uuid();
                Debugbar::startTime($debugbarUid, 'Load model ' . $name);
            } catch (\Throwable) {}
        }

        $class = '\src\Model\\' . $name;

        if (!class_exists($class)) {
            throw new NotFoundException('Not found model ' . $name);
        }

        /** @var AbstractModel $model */
        $model = new $class();

        if (!$model instanceof AbstractModel) {
            throw new NimbleException('Failed load model');
        }

        $model->name = $name;
        $model->prepareTableInstance();
        $model->controller = $this;
        $modelPropertyName = implode('', array_map('ucfirst', explode('_', $name)));

        if (property_exists($this, $modelPropertyName)) {
            $this->{$modelPropertyName} = $model;
        }

        $this->models[$modelPropertyName] = $model;

        if (Kernel::$activeDebugbar) {
            try {
                Debugbar::stopTime($debugbarUid);
            } catch (\Throwable) {}
        }

        return $model;
    }

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
        if (in_array($name, array_keys($this->models))) {
            return $this->models[$name];
        }

        $className = $this::class;

        throw new Exception("Undefined property: {$className}::{$name}", 2);
    }

}