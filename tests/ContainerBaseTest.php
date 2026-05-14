<?php

use NimblePHP\Framework\Container\ContainerBase;
use PHPUnit\Framework\TestCase;

class ContainerBaseTest extends TestCase
{
    protected function setUp(): void
    {
        $reflection = new ReflectionClass(ContainerBase::class);
        $instancesProperty = $reflection->getProperty('instances');
        $instancesProperty->setAccessible(true);
        $instancesProperty->setValue(null, []);
    }

    public function testGetInstanceReturnsSingletonPerSubclass(): void
    {
        $first = TestContainerBaseA::getInstance();
        $second = TestContainerBaseA::getInstance();
        $third = TestContainerBaseB::getInstance();

        $this->assertSame($first, $second);
        $this->assertNotSame($first, $third);
    }
}

class TestContainerBaseA extends ContainerBase
{
}

class TestContainerBaseB extends ContainerBase
{
}
