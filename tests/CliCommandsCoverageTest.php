<?php

require_once __DIR__ . '/CliCommandTestHelpers.php';

use NimblePHP\Framework\CLI\Commands\Completion;
use NimblePHP\Framework\CLI\Commands\Config;
use NimblePHP\Framework\CLI\Commands\Logs;
use NimblePHP\Framework\CLI\Commands\MakeController;
use NimblePHP\Framework\CLI\Commands\MakeModel;
use NimblePHP\Framework\CLI\Commands\Module;
use NimblePHP\Framework\CLI\Input;
use NimblePHP\Framework\DataStore;
use NimblePHP\Framework\Enums\ModuleVersionEnum;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Module\ModuleRegister;
use NimblePHP\Framework\Module\Interfaces\ModuleInterface;
use PHPUnit\Framework\TestCase;

class CliCommandsCoverageTest extends TestCase
{
    private array $envBackup;

    private string $projectPath;

    private string $scriptPath;

    protected function setUp(): void
    {
        $this->envBackup = $_ENV;
        $this->projectPath = sys_get_temp_dir() . '/nimble_cli_' . uniqid('', true);
        mkdir($this->projectPath, 0777, true);
        $this->scriptPath = $this->projectPath . '/nimble';
        file_put_contents($this->scriptPath, "#!/usr/bin/env php\n");
        Kernel::$projectPath = $this->projectPath;
        $_SERVER['SCRIPT_FILENAME'] = $this->scriptPath;
        $this->resetModuleRegister();
    }

    protected function tearDown(): void
    {
        $_ENV = $this->envBackup;
        $this->resetModuleRegister();
        $this->removeDirectory($this->projectPath);
    }

    public function testCompletionWritesShellScriptFromHandleAndGenerate(): void
    {
        $command = new Completion();
        $output = new RecordingOutput();

        $exitCode = $command->run(new Input('completion', [], []), $output);
        $command->generate();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('complete -F _nimble_completion nimble', $output->writes[0]);
        $this->assertStringContainsString('php "' . $this->scriptPath . '" --complete', $output->writes[0]);
    }

    public function testConfigShowStringifiesConfigurationValues(): void
    {
        $_ENV['FEATURE_NAME'] = 'demo';
        $_ENV['FEATURE_ENABLED'] = true;

        $command = new Config();
        $output = new RecordingOutput();

        $exitCode = $command->run(new Input('config:show', [], []), $output);

        $this->assertSame(0, $exitCode);
        $this->assertNotEmpty($output->kvPayloads);
        $this->assertSame('demo', $output->kvPayloads[0]['FEATURE_NAME']);
        $this->assertSame('True', $output->kvPayloads[0]['FEATURE_ENABLED']);
    }

    public function testMakeControllerCreatesControllerWithNormalizedRoute(): void
    {
        mkdir($this->projectPath . '/App/Controller', 0777, true);

        $command = new MakeController();
        $output = new RecordingOutput();
        $input = new Input(
            'make:controller',
            ['Task', '--route=tasks/{id}'],
            [0 => 'Task', 'route' => 'tasks/{id}'],
            [['name' => 'name']]
        );

        $exitCode = $command->run($input, $output);
        $filePath = $this->projectPath . '/App/Controller/Task.php';

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString("#[Route('/tasks/{id}')]", file_get_contents($filePath));
        $this->assertSame("Controller Task created in: {$filePath}", $output->successes[0]);
    }

    public function testMakeModelNormalizesNameAndCreatesFile(): void
    {
        mkdir($this->projectPath . '/App/Model', 0777, true);

        $command = new MakeModel();
        $output = new RecordingOutput();
        $input = new Input(
            'make:model',
            ['task_item'],
            [0 => 'task_item'],
            [['name' => 'name']]
        );

        $exitCode = $command->run($input, $output);
        $filePath = $this->projectPath . '/App/Model/TaskItemModel.php';

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('class TaskItemModel extends AbstractModel', file_get_contents($filePath));
        $this->assertSame('Model TaskItemModel created', $output->successes[0]);
    }

    public function testLogsClearFailsWhenLogsDirectoryMissing(): void
    {
        $command = new Logs();
        $output = new RecordingOutput();

        $exitCode = $command->logsClear($output);

        $this->assertSame(1, $exitCode);
        $this->assertSame('Not found /storage/logs', $output->errors[0]);
    }

    public function testLogsClearRemovesJsonLogFiles(): void
    {
        mkdir($this->projectPath . '/storage/logs', 0777, true);
        file_put_contents($this->projectPath . '/storage/logs/app.log.json', '{}');
        file_put_contents($this->projectPath . '/storage/logs/api.log.json', '{}');

        $command = new Logs();
        $output = new RecordingOutput();

        $exitCode = $command->logsClear($output);

        $this->assertSame(0, $exitCode);
        $this->assertFileDoesNotExist($this->projectPath . '/storage/logs/app.log.json');
        $this->assertFileDoesNotExist($this->projectPath . '/storage/logs/api.log.json');
        $this->assertSame('Success', $output->successes[0]);
    }

    public function testModuleInfoShowsWarningWhenNoModulesRegistered(): void
    {
        $command = new Module();
        $output = new RecordingOutput();

        $exitCode = $command->info($output);

        $this->assertSame(0, $exitCode);
        $this->assertSame('No modules found.', $output->warnings[0]);
    }

    public function testModuleInfoDisplaysSingleModuleDetails(): void
    {
        $config = new DataStore();
        $config->set('pkg_version', '1.2.3');
        $config->set('version', ModuleVersionEnum::V2);
        $config->set('path', '/tmp/example');
        $config->set('register', true);

        $classes = new DataStore();
        $classes->set('module', new CliCoverageModule());

        ModuleRegister::register('nimblephp/example-module', $config, '\\NimblePHP\\ExampleModule', $classes);

        $command = new Module();
        $output = new RecordingOutput();

        $exitCode = $command->info($output, 'nimblephp/example-module');

        $this->assertSame(0, $exitCode);
        $this->assertSame(['Module info', 'nimblephp/example-module'], $output->sections[0]);
        $this->assertSame('Example module', $output->kvPayloads[0]['Display name']);
        $this->assertSame('1.2.3', $output->kvPayloads[0]['Package version']);
        $this->assertSame('V2', $output->kvPayloads[0]['Framework version']);
        $this->assertSame('yes', $output->kvPayloads[0]['Registered']);
    }

    private function resetModuleRegister(): void
    {
        $reflection = new ReflectionClass(ModuleRegister::class);
        $property = $reflection->getProperty('modules');
        $property->setAccessible(true);
        $property->setValue(null, []);
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

class CliCoverageModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'Example module';
    }

    public function register(): void
    {
    }
}
