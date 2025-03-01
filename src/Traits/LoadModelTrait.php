<?php

namespace NimblePHP\framework\Traits;


use Krzysztofzylka\Reflection\Reflection;
use NimblePHP\framework\Abstracts\AbstractController;
use NimblePHP\framework\Abstracts\AbstractModel;
use NimblePHP\framework\Attributes\DependencyInjection\Inject;
use NimblePHP\framework\Exception\NimbleException;
use NimblePHP\framework\Exception\NotFoundException;
use NimblePHP\framework\Interfaces\ControllerInterface;
use NimblePHP\framework\Request;

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
        $class = '\App\Model\\' . $name;

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

        $reflection = new \ReflectionClass($model);

        foreach ($reflection->getProperties() as $property) {
            foreach ($property->getAttributes(Inject::class) as $attribute) {
                $inject = $attribute->newInstance();
                $inject->handle($model);
            }
        }

        $model->afterConstruct();

        return $model;
    }

}