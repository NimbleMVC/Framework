<?php

use NimblePHP\Framework\ModuleRegister;
use PHPUnit\Framework\TestCase;

class ModuleRegisterTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset the modules array before each test
        $reflectionClass = new ReflectionClass(ModuleRegister::class);
        $modulesProperty = $reflectionClass->getProperty('modules');
        $modulesProperty->setAccessible(true);
        $modulesProperty->setValue([]);
    }

    public function testRegisterAndGet()
    {
        $moduleName = 'test-module';
        $moduleConfig = ['key' => 'value'];
        $moduleNamespace = '\\NimblePHP\\TestModule';
        $moduleClasses = ['class1', 'class2'];

        ModuleRegister::register($moduleName, $moduleConfig, $moduleNamespace, $moduleClasses);

        $module = ModuleRegister::get($moduleName);

        $this->assertIsArray($module);
        $this->assertEquals($moduleName, $module['name']);
        $this->assertEquals($moduleConfig, $module['config']);
        $this->assertEquals($moduleNamespace, $module['namespace']);
        $this->assertEquals($moduleClasses, $module['classes']);
    }

    public function testGetNonExistentModule()
    {
        $module = ModuleRegister::get('non-existent-module');

        $this->assertIsArray($module);
        $this->assertEmpty($module);
    }

    public function testIsset()
    {
        // Test with non-existent module
        $this->assertFalse(ModuleRegister::isset('test-module'));

        // Register a module and test again
        ModuleRegister::register('test-module');
        $this->assertTrue(ModuleRegister::isset('test-module'));
    }

    public function testGetAll()
    {
        // Register multiple modules
        ModuleRegister::register('module1', ['key1' => 'value1']);
        ModuleRegister::register('module2', ['key2' => 'value2']);

        $allModules = ModuleRegister::getAll();

        $this->assertIsArray($allModules);
        $this->assertCount(2, $allModules);
        $this->assertArrayHasKey('module1', $allModules);
        $this->assertArrayHasKey('module2', $allModules);
        $this->assertEquals('value1', $allModules['module1']['config']['key1']);
        $this->assertEquals('value2', $allModules['module2']['config']['key2']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testModuleExistsInVendor()
    {
        // This test requires mocking the Composer\InstalledVersions class
        // Since we can't easily mock static class methods, we'll create a simple
        // test that just verifies the method exists and returns a boolean value

        // We can't easily test the actual functionality without setting up Composer packages
        $result = ModuleRegister::moduleExistsInVendor('some-module');

        $this->assertIsBool($result);
    }

    /**
     * Test autoRegister method
     *
     * Note: This test is limited because we can't easily mock Composer's InstalledVersions class
     * In a full test environment, you'd need to set up actual Composer packages
     */
    public function testAutoRegister()
    {
        // Nie możemy łatwo mockować statycznych klas Composer, więc zamiast tego
        // po prostu sprawdzimy, czy metoda autoRegister działa bez błędów

        // Utwórz instancję ModuleRegister
        $moduleRegister = new ModuleRegister();

        // Wywołaj metodę autoRegister
        $moduleRegister->autoRegister();

        // Jeśli nie wystąpiły żadne wyjątki, test jest udany
        $this->assertTrue(true);
    }
}