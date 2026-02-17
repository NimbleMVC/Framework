<?php

namespace NimblePHP\Framework\Traits;

use NimblePHP\Framework\Abstracts\AbstractController;
use NimblePHP\Framework\Abstracts\AbstractModel;
use NimblePHP\Framework\Attributes\Http\Action;
use NimblePHP\Framework\DependencyInjector;
use NimblePHP\Framework\Enums\ModelTypeEnum;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Exception\NotFoundException;
use NimblePHP\Framework\Interfaces\ControllerInterface;
use NimblePHP\Framework\Request;
use ReflectionClass;

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
    #[Action('disabled')]
    public function loadModel(string $name): object
    {
        if (str_starts_with($name, 'App\Model') || str_starts_with($name, 'NimblePHP\\')) {
            $modelClassName = '\\' . $name;
        } else {
            $modelClassName = '\App\Model\\' . $name;
        }

        if (!class_exists($modelClassName)) {
            throw new NotFoundException('Not found model ' . $name);
        }

        /** @var AbstractModel $model */
        $model = new $modelClassName();

        if (str_ends_with($modelClassName, 'Model')) {
            $model->modelType = ModelTypeEnum::V2;
        }

        if (!$model instanceof AbstractModel) {
            throw new NimbleException('Failed load model');
        }

        $model->name = str_replace(['App\Model\\', '\\'], ['', '_'], $model->modelType === ModelTypeEnum::V2 ? substr($name, 0, -5) : $name);

        $model->prepareTableInstance();

        if ($this instanceof ControllerInterface) {
            $model->controller = $this;
        } elseif ((new ReflectionClass($this))->hasProperty('controller')) {
            $model->controller = $this->controller;
        } else {
            $controller = new class extends AbstractController {
            };
            $controller->name = '';
            $controller->action = '';
            $controller->request = new Request();
            $model->controller = $controller;
        }

        DependencyInjector::inject($model);
        $model->afterConstruct();

        return $model;
    }

}