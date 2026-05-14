<?php

use NimblePHP\Framework\Libs\Classes;
use PHPUnit\Framework\TestCase;

class ClassesTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/nimble_classes_' . uniqid('', true);
        mkdir($this->tempDir . '/Sub', 0777, true);
        file_put_contents($this->tempDir . '/RootClass.php', '<?php class RootClass {}');
        file_put_contents($this->tempDir . '/Sub/NestedClass.php', '<?php class NestedClass {}');
        file_put_contents($this->tempDir . '/Sub/ignored.txt', 'ignore');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testGetAllClassesReturnsPhpClassesFromDirectoryTree(): void
    {
        $classes = Classes::getAllClasses($this->tempDir, 'App\\Example');
        sort($classes);

        $this->assertSame([
            'App\\Example\\RootClass',
            'App\\Example\\Sub\\NestedClass',
        ], $classes);
    }

    public function testGetAllClassesReturnsEmptyArrayForMissingDirectory(): void
    {
        $this->assertSame([], Classes::getAllClasses($this->tempDir . '/missing', 'App\\Example'));
    }

    public function testFindClassNameChecksDirectAndPrefixedClassNames(): void
    {
        if (!class_exists('TestSupport\\ClassesAlias')) {
            class_alias(stdClass::class, 'TestSupport\\ClassesAlias');
        }

        $this->assertSame(DateTime::class, Classes::findClassName(DateTime::class, 'TestSupport\\'));
        $this->assertSame('TestSupport\\ClassesAlias', Classes::findClassName('ClassesAlias', 'TestSupport\\'));
        $this->assertNull(Classes::findClassName('DefinitelyMissingClass', 'TestSupport\\'));
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}
