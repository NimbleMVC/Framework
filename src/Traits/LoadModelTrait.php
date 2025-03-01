<?php

namespace NimblePHP\framework\Traits;


use Krzysztofzylka\Reflection\Reflection;
use NimblePHP\framework\Abstracts\AbstractController;
use NimblePHP\framework\Abstracts\AbstractModel;
use NimblePHP\framework\Exception\NimbleException;
use NimblePHP\framework\Exception\NotFoundException;
use NimblePHP\framework\Interfaces\ControllerInterface;
use NimblePHP\framework\Interfaces\ModelInterface;
use NimblePHP\framework\Request;
use NimblePHP\framework\Response;

trait LoadModelTrait
{

    /**
     * Load model
     * @param string $name
     * @return AbstractModel
     * @throws NimbleException
     * @throws NotFoundException
     */
    public function loadModel(string $name): AbstractModel
    {
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
            $model->controller = $controller;
        }

        $modelPropertyName = implode('', array_map('ucfirst', explode('_', $name)));

        if (property_exists($this, $modelPropertyName)) {
            $this->{$modelPropertyName} = $model;
        }

        $model->afterConstruct();

        return $model;
    }

}