<?php

namespace Nimblephp\framework\Traits;


use Krzysztofzylka\Reflection\Reflection;
use Nimblephp\debugbar\Debugbar;
use Nimblephp\framework\Abstracts\AbstractController;
use Nimblephp\framework\Abstracts\AbstractModel;
use Nimblephp\framework\Exception\NimbleException;
use Nimblephp\framework\Exception\NotFoundException;
use Nimblephp\framework\Interfaces\ControllerInterface;
use Nimblephp\framework\Interfaces\ModelInterface;
use Nimblephp\framework\Kernel;
use Nimblephp\framework\Request;
use Nimblephp\framework\Response;

trait LoadModelTrait
{

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

        if ($this instanceof ControllerInterface) {
            $model->controller = $this;
        } elseif (Reflection::classHasProperty($this, 'controller')) {
            $model->controller = $this->controller;
        } else {
            $controller = new class extends AbstractController {};
            $controller->name = '';
            $controller->action = '';
            $controller->request = new Request();
            $controller->response = new Response();
            $model->controller = $controller;
        }

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
     * Get model helper
     * @param string $name
     * @return mixed|null
     */
    public function __getModel(string $name): ?ModelInterface
    {
        if (in_array($name, array_keys($this->models))) {
            return $this->models[$name];
        }

        return null;
    }

}