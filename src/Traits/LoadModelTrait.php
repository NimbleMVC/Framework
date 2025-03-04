<?php

namespace NimblePHP\Framework\Traits;


use Krzysztofzylka\Reflection\Reflection;
use NimblePHP\Framework\Abstracts\AbstractController;
use NimblePHP\Framework\Abstracts\AbstractModel;
use NimblePHP\Framework\DependencyInjector;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Exception\NotFoundException;
use NimblePHP\Framework\Interfaces\ControllerInterface;
use NimblePHP\Framework\Request;

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

        DependencyInjector::inject($model);
        $model->afterConstruct();

        return $model;
    }

}