<?php

use NimblePHP\Framework\Container\ServiceContainer;
use NimblePHP\Framework\Request;
use NimblePHP\Framework\Response;
use PHPUnit\Framework\TestCase;

class ServiceContainerTest extends TestCase
{
    private ServiceContainer $container;

    protected function setUp(): void
    {
        $this->container = new ServiceContainer();
    }

    public function testSetAndGetService()
    {
        $request = new Request();
        $this->container->set('request', $request);

        $retrievedRequest = $this->container->get('request');
        $this->assertSame($request, $retrievedRequest);
    }

    public function testSetAndGetFactory()
    {
        $this->container->setFactory('response', function($container) {
            return new Response();
        });

        $response1 = $this->container->get('response');
        $response2 = $this->container->get('response');

        $this->assertInstanceOf(Response::class, $response1);
        $this->assertInstanceOf(Response::class, $response2);
        $this->assertNotSame($response1, $response2);
    }

    public function testHasService()
    {
        $this->assertFalse($this->container->has('nonexistent'));

        $this->container->set('test', 'value');
        $this->assertTrue($this->container->has('test'));
    }

    public function testHasFactory()
    {
        $this->container->setFactory('test', function() { return 'value'; });
        $this->assertTrue($this->container->has('test'));
    }

    public function testRemoveService()
    {
        $this->container->set('test', 'value');
        $this->assertTrue($this->container->has('test'));

        $this->container->remove('test');
        $this->assertFalse($this->container->has('test'));
    }

    public function testRemoveFactory()
    {
        $this->container->setFactory('test', function() { return 'value'; });
        $this->assertTrue($this->container->has('test'));

        $this->container->remove('test');
        $this->assertFalse($this->container->has('test'));
    }

    public function testSetAlias()
    {
        $this->container->set('original', 'value');
        $this->container->setAlias('alias', 'original');

        $this->assertEquals('value', $this->container->get('alias'));
    }

    public function testCircularAlias()
    {
        $this->container->set('service1', 'value1');
        $this->container->setAlias('alias1', 'service1');
        $this->container->setAlias('alias2', 'service1');

        $this->assertEquals('value1', $this->container->get('alias2'));
    }

    public function testGetResolvedServices()
    {
        $this->container->set('service1', 'value1');
        $this->container->set('service2', 'value2');

        $this->container->get('service1');

        $resolved = $this->container->getResolvedServices();
        $this->assertContains('service1', $resolved);
        $this->assertNotContains('service2', $resolved);
    }

    public function testGetRegisteredServices()
    {
        $this->container->set('service1', 'value1');
        $this->container->setFactory('factory1', function() { return 'value'; });

        $registered = $this->container->getRegisteredServices();
        $this->assertContains('service1', $registered);
        $this->assertContains('factory1', $registered);
    }

    public function testClear()
    {
        $this->container->set('service1', 'value1');
        $this->container->setFactory('factory1', function() { return 'value'; });
        $this->container->setAlias('alias1', 'service1');

        $this->container->clear();

        $this->assertFalse($this->container->has('service1'));
        $this->assertFalse($this->container->has('factory1'));
        $this->assertEmpty($this->container->getRegisteredServices());
        $this->assertEmpty($this->container->getResolvedServices());
    }

    public function testSetEmptyIdThrowsException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Service ID cannot be empty');
        $this->container->set('', 'value');
    }

    public function testSetFactoryEmptyIdThrowsException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Service ID cannot be empty');
        $this->container->setFactory('', function() { return 'value'; });
    }

    public function testGetEmptyIdThrowsException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Service ID cannot be empty');
        $this->container->get('');
    }

    public function testGetNonexistentServiceThrowsException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Service 'nonexistent' not found");
        $this->container->get('nonexistent');
    }

    public function testSetAliasEmptyAliasThrowsException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Alias and target ID cannot be empty');
        $this->container->setAlias('', 'target');
    }

    public function testSetAliasEmptyTargetThrowsException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Alias and target ID cannot be empty');
        $this->container->setAlias('alias', '');
    }

    public function testSetAliasSelfReferenceThrowsException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Alias cannot reference itself');
        $this->container->setAlias('alias', 'alias');
    }

    public function testCircularDependencyDetection()
    {
        $this->container->setFactory('service1', function($container) {
            return $container->get('service2');
        });

        $this->container->setFactory('service2', function($container) {
            return $container->get('service1');
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Circular dependency detected for service: service1');
        $this->container->get('service1');
    }

    public function testFactoryWithContainerDependency()
    {
        $this->container->set('dependency', 'dep_value');
        $this->container->setFactory('service', function($container) {
            return $container->get('dependency');
        });

        $result = $this->container->get('service');
        $this->assertEquals('dep_value', $result);
    }

    public function testServiceOverwrite()
    {
        $this->container->set('test', 'value1');
        $this->container->set('test', 'value2');

        $this->assertEquals('value2', $this->container->get('test'));
    }

    public function testFactoryOverwrite()
    {
        $this->container->setFactory('test', function() { return 'value1'; });
        $this->container->setFactory('test', function() { return 'value2'; });

        $this->assertEquals('value2', $this->container->get('test'));
    }

    public function testServiceOverwritesFactory()
    {
        $this->container->setFactory('test', function() { return 'factory_value'; });
        $this->container->set('test', 'service_value');

        $this->assertEquals('factory_value', $this->container->get('test'));
    }

    public function testFactoryOverwritesService()
    {
        $this->container->set('test', 'service_value');
        $this->container->setFactory('test', function() { return 'factory_value'; });

        $this->assertEquals('factory_value', $this->container->get('test'));
    }

    public function testRemoveWithAlias()
    {
        $this->container->set('original', 'value');
        $this->container->setAlias('alias', 'original');

        $this->container->remove('original');

        $this->assertFalse($this->container->has('original'));
        $this->assertFalse($this->container->has('alias'));
    }

    public function testHasWithEmptyId()
    {
        $this->assertFalse($this->container->has(''));
    }

    public function testRemoveWithEmptyId()
    {
        $this->container->remove('');
        $this->assertTrue(true);
    }
}