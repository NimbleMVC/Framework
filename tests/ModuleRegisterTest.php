<?php

use NimblePHP\Framework\DataStore;
use NimblePHP\Framework\Module\ModuleRegister;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

class ModuleRegisterTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset the modules array before each test
        $reflectionClass = new ReflectionClass(ModuleRegister::class);
        $modulesProperty = $reflectionClass->getProperty('modules');
        $modulesProperty->setAccessible(true);
        $modulesProperty->setValue(null, []);
    }

    public function testRegisterAndGet()
    {
        $moduleName = 'test-module';
        $moduleConfig = new DataStore();
        $moduleConfig->set('key', 'value');
        $moduleNamespace = '\\NimblePHP\\TestModule';
        $moduleClasses = new DataStore();
        $moduleClasses->set('class1', 'App\\Class1');
        $moduleClasses->set('class2', 'App\\Class2');

        ModuleRegister::register($moduleName, $moduleConfig, $moduleNamespace, $moduleClasses);

        $module = ModuleRegister::get($moduleName);

        $this->assertIsArray($module);
        $this->assertEquals($moduleName, $module['name']);
        $this->assertInstanceOf(DataStore::class, $module['config']);
        $this->assertEquals('value', $module['config']->get('key'));
        $this->assertEquals($moduleNamespace, $module['namespace']);
        $this->assertInstanceOf(DataStore::class, $module['classes']);
        $this->assertEquals('App\\Class1', $module['classes']->get('class1'));
        $this->assertEquals('App\\Class2', $module['classes']->get('class2'));
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
        ModuleRegister::register('test-module', new DataStore());
        $this->assertTrue(ModuleRegister::isset('test-module'));
    }

    public function testGetAll()
    {
        // Register multiple modules
        $module1Config = new DataStore();
        $module1Config->set('key1', 'value1');
        $module2Config = new DataStore();
        $module2Config->set('key2', 'value2');

        ModuleRegister::register('module1', $module1Config);
        ModuleRegister::register('module2', $module2Config);

        $allModules = ModuleRegister::getAll();

        $this->assertIsArray($allModules);
        $this->assertCount(2, $allModules);
        $this->assertArrayHasKey('module1', $allModules);
        $this->assertArrayHasKey('module2', $allModules);
        $this->assertEquals('value1', $allModules['module1']['config']->get('key1'));
        $this->assertEquals('value2', $allModules['module2']['config']->get('key2'));
    }

    #[RunInSeparateProcess]
    public function testModuleExistsInVendor()
    {
        $this->assertTrue(ModuleRegister::moduleExistsInVendor('nimblephp/framework'));
        $this->assertFalse(ModuleRegister::moduleExistsInVendor('nimblephp/non-existent-package'));
    }

    /**
     * Test autoRegister method
     *
     * Note: This test is limited because we can't easily mock Composer's InstalledVersions class
     * In a full test environment, you'd need to set up actual Composer packages
     */
    public function testAutoRegister()
    {
        $moduleRegister = new ModuleRegister();
        $moduleRegister->autoRegister();
        $this->assertIsArray(ModuleRegister::getAll());
    }
}
