<?php

use NimblePHP\Framework\CLI\ConsoleHelper;
use NimblePHP\Framework\Kernel;
use PHPUnit\Framework\TestCase;

class ConsoleHelperTest extends TestCase
{
    private array $envBackup;

    private string $projectPath;

    protected function setUp(): void
    {
        $this->envBackup = $_ENV;
        $this->projectPath = sys_get_temp_dir() . '/nimble_console_helper_' . uniqid('', true);
        mkdir($this->projectPath, 0777, true);
    }

    protected function tearDown(): void
    {
        $_ENV = $this->envBackup;
        $this->removeDirectory($this->projectPath);
    }

    public function testInitProjectPathUsesCurrentWorkingDirectory(): void
    {
        $cwd = getcwd();
        chdir($this->projectPath);

        try {
            ConsoleHelper::initProjectPath();
            $this->assertSame($this->projectPath, Kernel::$projectPath);
        } finally {
            chdir($cwd);
        }
    }

    public function testProjectIsInitializedWhenAnyRequiredDirectoryExists(): void
    {
        mkdir($this->projectPath . '/App', 0777, true);

        $this->assertTrue(ConsoleHelper::projectIsInitialized($this->projectPath));
        $this->assertFalse(ConsoleHelper::projectIsInitialized($this->projectPath . '/missing'));
    }

    public function testLoadConfigLoadsProjectEnvOverrides(): void
    {
        Kernel::$projectPath = $this->projectPath;
        file_put_contents($this->projectPath . '/.env', "APP_NAME=Base\nDEBUG=false\n");
        file_put_contents($this->projectPath . '/.env.local', "DEBUG=true\nCUSTOM_ENV=local\n");

        ConsoleHelper::loadConfig();

        $this->assertSame('Base', (string) $_ENV['APP_NAME']);
        $this->assertTrue((bool) $_ENV['DEBUG']);
        $this->assertSame('local', (string) $_ENV['CUSTOM_ENV']);
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
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
