<?php

use NimblePHP\Framework\DependencyInjector;
use NimblePHP\Framework\Attributes\DependencyInjection\Inject;
use NimblePHP\Framework\Interfaces\ControllerInterface;
use PHPUnit\Framework\TestCase;

// Test classes for dependency injection
class TestController implements ControllerInterface
{
    #[Inject(TestService::class)]
    public $service;

    public function loadModel(string $name): object
    {
        // Mock implementation for testing
        return new TestModel();
    }

    public function log(string $message, string $level = 'INFO', array $content = []): bool
    {
        return true;
    }

    public function afterConstruct(): void
    {
    }
}

class TestService
{
    public function doSomething(): string
    {
        return 'Service result';
    }
}

class TestModel
{
    public $controller;

    #[Inject(TestRepository::class)]
    public $repository;
}

class TestRepository
{
    public function find(): string
    {
        return 'Repository result';
    }
}

class TestClassWithoutInject
{
    public $normalProperty;
}

class DependencyInjectorTest extends TestCase
{
    public function testInjectWithController()
    {
        $controller = new TestController();

        // Before injection
        $this->checkObjectNotHasProperty('service', $controller);

        // Perform injection
        DependencyInjector::inject($controller);

        // After injection
        $this->checkObjectHasProperty('service', $controller);
        $this->assertInstanceOf(TestService::class, $controller->service);
        $this->assertEquals('Service result', $controller->service->doSomething());
    }

    public function testInjectWithModel()
    {
        $model = new TestModel();
        $controller = new TestController();
        $model->controller = $controller;

        // Before injection
        $this->checkObjectNotHasProperty('repository', $model);

        // Perform injection
        DependencyInjector::inject($model);

        // After injection
        $this->checkObjectHasProperty('repository', $model);
        $this->assertInstanceOf(TestRepository::class, $model->repository);
        $this->assertEquals('Repository result', $model->repository->find());
    }

    public function testInjectWithNoDependencies()
    {
        $object = new TestClassWithoutInject();

        // Set a property value
        $object->normalProperty = 'value';

        // Perform injection
        DependencyInjector::inject($object);

        // Verify the property wasn't changed
        $this->assertEquals('value', $object->normalProperty);
    }

    /**
     * Helper method to check if an object has a property that is set
     */
    private function checkObjectHasProperty(string $propertyName, object $object): void
    {
        $this->assertTrue(property_exists($object, $propertyName) && isset($object->$propertyName));
    }

    /**
     * Helper method to check if an object doesn't have a property that is set
     */
    private function checkObjectNotHasProperty(string $propertyName, object $object): void
    {
        $this->assertFalse(property_exists($object, $propertyName) && isset($object->$propertyName));
    }
}