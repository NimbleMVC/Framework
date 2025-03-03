<?php

namespace NimblePHP\framework\Traits;


use Krzysztofzylka\Reflection\Reflection;
use NimblePHP\framework\Abstracts\AbstractController;
use NimblePHP\framework\Abstracts\AbstractModel;
use NimblePHP\framework\Exception\NimbleException;
use NimblePHP\framework\Exception\NotFoundException;
use NimblePHP\framework\Interfaces\ControllerInterface;
use NimblePHP\framework\Request;

trait LoadModelTrait
{

    /**
     * Load model
     * @template T
     * @param class-string<T> $name
     * @return T
     * @throws NimbleException
     * @throws NotFoundException
     */
    public function loadModel(string $name): object
    {
        if (str_starts_with($name, 'App\Model')) {
            $class = '\\' . $name;
        } else {
            $class = '\App\Model\\' . $name;
        }

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
            $model->controller = &$this;
        } elseif (Reflection::classHasProperty($this, 'controller')) {
            $model->controller = &$this->controller;
        } else {
            $controller = new class extends AbstractController {};
            $controller->name = '';
            $controller->action = '';
            $controller->request = new Request();
            $model->controller = &$controller;
        }

        $model->afterConstruct();

        return $model;
    }

}