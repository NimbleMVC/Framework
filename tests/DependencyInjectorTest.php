<?php

use NimblePHP\Framework\DependencyInjector;
use NimblePHP\Framework\Attributes\DependencyInjection\Inject;
use NimblePHP\Framework\Interfaces\ControllerInterface;
use PHPUnit\Framework\TestCase;

class DependencyInjectorTest extends TestCase
{
    public function testInjectWithController()
    {
        $controller = new TestController();
        DependencyInjector::inject($controller);
        $this->assertInstanceOf(TestService::class, $controller->service);
        $this->assertEquals('Service result', $controller->service->doSomething());
    }

    public function testInjectWithModel()
    {
        $model = new TestModel();
        $controller = new TestController();
        $model->controller = $controller;
        DependencyInjector::inject($model);
        $this->assertInstanceOf(TestRepository::class, $model->repository);
        $this->assertEquals('Repository result', $model->repository->find());
    }

    public function testInjectWithNoDependencies()
    {
        $object = new TestClassWithoutInject();
        $object->normalProperty = 'value';
        DependencyInjector::inject($object);
        $this->assertEquals('value', $object->normalProperty);
    }

    public function testInjectWithNonExistentClass()
    {
        $controller = new TestControllerWithInvalidInject();
        $this->expectException(\Error::class);
        DependencyInjector::inject($controller);
    }
}

class TestController implements ControllerInterface
{
    #[Inject(TestService::class)]
    public $service;
    public function loadModel(string $name): object { return new TestModel(); }
    public function log(string $message, string $level = 'INFO', array $content = []): bool { return true; }
    public function afterConstruct(): void {}
}

class TestControllerWithInvalidInject implements ControllerInterface
{
    #[Inject('NonExistentClass')]
    public $service;
    public function loadModel(string $name): object { return new TestModel(); }
    public function log(string $message, string $level = 'INFO', array $content = []): bool { return true; }
    public function afterConstruct(): void {}
}

class TestService { public function doSomething(): string { return 'Service result'; } }
class TestModel { public $controller; #[Inject(TestRepository::class)] public $repository; }
class TestRepository { public function find(): string { return 'Repository result'; } }
class TestClassWithoutInject { public $normalProperty; }