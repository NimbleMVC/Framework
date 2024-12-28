<?php

namespace Krzysztofzylka\Reflection;

use ReflectionClass;
use ReflectionException;
use ReflectionObject;
use ReflectionProperty;

class Reflection
{

    /**
     * Get class directory path
     * @throws ReflectionException
     */
    public static function getDirectoryPath($objectOrClass): string
    {
        $reflection = new ReflectionClass($objectOrClass);

        return dirname($reflection->getFileName());
    }

    /**
     * Ger all public variables list from objects
     * @param Object $object
     * @return array
     */
    public static function getPublicPropertyList(Object $object): array
    {
        $return = [];
        $reflection = new ReflectionObject($object);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) AS $property)  {
            $key = $property->getName();
            $value = $property->getValue($object);

            $return[$key] = is_object($value) ? self::getPublicPropertyList($value) : $value;
        }

        return $return;
    }

    /**
     * Get comments from class methods
     * @param string|object $objectOrClass object
     * @param string $method Method name
     * @param ?string $type comment name without @ (example: for @return write return)
     * @return array [[key, value], [key, value]], if type is not null [key, value]
     * @throws ReflectionException
     */
    public static function getClassMethodComment($objectOrClass, string $method, ?string $type = null): array
    {
        $reflector = new ReflectionClass($objectOrClass);
        $comment = $reflector->getMethod($method)->getDocComment();
        $explodeComment = explode(PHP_EOL, $comment);
        $comments = self::cleanComment($explodeComment);

        if (!is_null($type)) {
            $return = [];

            foreach ($comments as $commentValue) {
                if ($commentValue[0] === $type) {
                    $return[] = $commentValue[1];
                }
            }

            return $return;
        } else {
            return $comments;
        }
    }

    /**
     * Search in commend with name and value
     * @param array $comments comments list from getClassMethodComment
     * @param string $name comment name without @ (example: for @return write return)
     * @param ?string $value comment value (default null)
     * @return array|bool array if $value is null
     */
    public static function findClassComment(array $comments, string $name, ?string $value = null)
    {
        $return = [];

        foreach ($comments as $commentValue) {
            if ($commentValue[0] === $name) {
                $return[] = $commentValue[1];
            }
        }

        if (!is_null($value)) {
            return in_array($value, $return);
        }

        return $return;
    }

    /**
     * Get comment from class
     * @param string|object $objectOrClass object
     * @param string|null $type
     * @return array
     * @throws ReflectionException
     */
    public static function getClassComment($objectOrClass, ?string $type = null): array
    {
        $reflector = new ReflectionClass($objectOrClass);
        $comment = $reflector->getDocComment();
        $explodeComment = explode(PHP_EOL, $comment);
        $comments = self::cleanComment($explodeComment);

        if (!is_null($type)) {
            $return = [];

            foreach ($comments as $commentValue) {
                if ($commentValue[0] === $type) {
                    $return[] = $commentValue[1];
                }
            }

            return $return;
        } else {
            return $comments;
        }
    }

    /**
     * Get class methods
     * @param $objectOrClass
     * @return array
     * @throws ReflectionException
     */
    public static function getClassMethods($objectOrClass): array
    {
        $reflector = new ReflectionClass($objectOrClass);
        $methods = $reflector->getMethods();

        foreach ($methods as $method) {
            $parameters = [];

            foreach ($method->getParameters() as $parameter) {
                $data = [
                    'name' => $parameter->getName(),
                    'type' => $parameter->getType()->getName(),
                    'position' => $parameter->getPosition()
                ];

                if ($parameter->isDefaultValueAvailable()) {
                    $data['default_value'] = $parameter->getDefaultValue();
                }


                $parameters[] = $data;
            }

            $return[] = [
                'method' => $method->getName(),
                'comment' => self::getClassMethodComment($objectOrClass, $method->getName()),
                'parameters' => $parameters
            ];
        }

        return $return;
    }

    /**
     * Class has property
     * @param object $className
     * @param string $propertyName
     * @return bool
     */
    public static function classHasProperty(object $className, string $propertyName): bool
    {
        $reflection = new ReflectionClass($className);

        return $reflection->hasProperty('controller');
    }

    /**
     * Clear comment
     * @param array $comments PHPDocs data example ['/*', '* value']
     * @return array data as [key, value] (example [return, void])
     */
    private static function cleanComment(array $comments): array
    {
        $cleanComment = [];

        foreach ($comments as $comment) {
            $comment = trim($comment);

            if ($comment === '*/' || $comment === '/**') {
                continue;
            }

            if (PHP_VERSION_ID >= 80000) {
                if (str_starts_with($comment, '* ')) {
                    $comment = substr($comment, 2);
                }

                if (str_starts_with($comment, '@')) {
                    $explodeComment = explode(' ', $comment, 2);
                    $cleanComment[] = [str_replace('@', '', $explodeComment[0]), $explodeComment[1] ?? null];

                    continue;
                }
            } else {
                if (substr($comment, 2) === '* ') {
                    $comment = substr($comment, 2);
                }

                if (substr($comment, 1) === '@') {
                    $explodeComment = explode(' ', $comment, 2);
                    $cleanComment[] = [str_replace('@', '', $explodeComment[0]), $explodeComment[1] ?? null];

                    continue;
                }
            }

            $cleanComment[] = ['', $comment];
        }

        return $cleanComment;
    }

}