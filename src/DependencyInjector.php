<?php

namespace NimblePHP\Framework;

use NimblePHP\Framework\Attributes\DependencyInjection\Inject;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Exception\NotFoundException;
use NimblePHP\Framework\Interfaces\ControllerInterface;
use NimblePHP\Framework\Interfaces\ModelInterface;
use NimblePHP\Framework\Traits\LoadModelTrait;

/**
 * Dependency injector
 */
class DependencyInjector
{

    use LoadModelTrait;

    /**
     * Inject
     * @param object $object
     * @return void
     * @throws NimbleException
     * @throws NotFoundException
     */
    public static function inject(object $object): void {
        $reflection = new \ReflectionObject($object);

        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(Inject::class);

            if (!empty($attributes)) {
                $attribute = $attributes[0]->newInstance();

                if (str_starts_with($attribute->className, 'App\Model')) {
                    $instance = (new self())->loadModel($attribute->className);

                    if ($object instanceof ControllerInterface) {
                        $instance->controller = $object;
                    } elseif ($object instanceof ModelInterface || property_exists($object, 'controller')) {
                        $instance->controller = $object->controller;
                    }
                } else {
                    $instance = new $attribute->className();
                }

                $property->setValue($object, $instance);
            }
        }
    }

}