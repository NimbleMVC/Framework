<?php

require_once __DIR__ . '/CliCommandTestHelpers.php';

use NimblePHP\Framework\CLI\Commands\Project;
use NimblePHP\Framework\Kernel;
use PHPUnit\Framework\TestCase;

class ProjectCommandTest extends TestCase
{
    private string $projectPath;

    protected function setUp(): void
    {
        $this->projectPath = sys_get_temp_dir() . '/nimble_project_command_' . uniqid('', true);
        mkdir($this->projectPath, 0777, true);
        Kernel::$projectPath = $this->projectPath;
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectPath);
    }

    public function testTemplateHelpersReturnExpectedContent(): void
    {
        mkdir($this->projectPath . '/public', 0777, true);
        $command = new Project();

        $index = $this->invokeProjectMethod($command, 'indexTemplate', 'demo');
        $htaccess = $this->invokeProjectMethod($command, 'htaccessTemplate');
        $gitignore = $this->invokeProjectMethod($command, 'gitignoreTemplate');
        $envlocal = $this->invokeProjectMethod($command, 'envlocalTemplate');

        $this->assertStringContainsString("require('", $index);
        $this->assertStringContainsString('vendor/autoload.php', $index);
        $this->assertStringContainsString('RewriteEngine On', $htaccess);
        $this->assertStringContainsString('storage/cache/*', $gitignore);
        $this->assertSame('DEBUG=true', $envlocal);
    }

    public function testReadComposerJsonReturnsDecodedArrayOrEmptyArray(): void
    {
        $command = new Project();
        file_put_contents($this->projectPath . '/composer.json', json_encode(['name' => 'demo/project']));

        $this->assertSame(['name' => 'demo/project'], $this->invokeProjectMethod($command, 'readComposerJson'));

        unlink($this->projectPath . '/composer.json');

        $this->assertSame([], $this->invokeProjectMethod($command, 'readComposerJson'));
    }

    public function testSummarizeDoctorChecksCountsStatusesAndRecommendations(): void
    {
        $command = new Project();
        $summary = $this->invokeProjectMethod($command, 'summarizeDoctorChecks', [
            ['status' => 'ok', 'label' => 'A', 'details' => 'fine'],
            ['status' => 'warn', 'label' => 'B', 'details' => 'warn', 'recommendation' => 'Do thing'],
            ['status' => 'error', 'label' => 'C', 'details' => 'bad', 'recommendation' => 'Do thing'],
            ['status' => 'error', 'label' => 'D', 'details' => 'bad', 'recommendation' => 'Fix D'],
        ]);

        $this->assertSame(4, $summary['checks']);
        $this->assertSame(1, $summary['ok']);
        $this->assertSame(1, $summary['warnings']);
        $this->assertSame(2, $summary['errors']);
        $this->assertSame(['Do thing', 'Fix D'], $summary['recommendations']);
    }

    public function testLooksLikePlaceholderValueAndFormatBytesHelpers(): void
    {
        $command = new Project();

        $this->assertTrue($this->invokeProjectMethod($command, 'looksLikePlaceholderValue', 'changeMe123'));
        $this->assertFalse($this->invokeProjectMethod($command, 'looksLikePlaceholderValue', 'real-secret'));
        $this->assertSame('512 B', $this->invokeProjectMethod($command, 'formatBytes', 512));
        $this->assertSame('2 KB', $this->invokeProjectMethod($command, 'formatBytes', 2048));
        $this->assertSame('1 MB', $this->invokeProjectMethod($command, 'formatBytes', 1024 * 1024));
    }

    public function testRenderDoctorCheckRoutesMessageByStatus(): void
    {
        $command = new Project();
        $output = new RecordingOutput();

        $this->invokeProjectMethod($command, 'renderDoctorCheck', $output, [
            'status' => 'ok',
            'label' => 'Check',
            'details' => 'done',
        ]);
        $this->invokeProjectMethod($command, 'renderDoctorCheck', $output, [
            'status' => 'warn',
            'label' => 'Check',
            'details' => 'warn',
        ]);
        $this->invokeProjectMethod($command, 'renderDoctorCheck', $output, [
            'status' => 'error',
            'label' => 'Check',
            'details' => 'bad',
        ]);

        $this->assertSame('[OK] Check - done', $output->successes[0]);
        $this->assertSame('[WARN] Check - warn', $output->warnings[0]);
        $this->assertSame('[ERROR] Check - bad', $output->errors[0]);
    }

    private function invokeProjectMethod(Project $project, string $method, mixed ...$args): mixed
    {
        $reflectionMethod = new ReflectionMethod(Project::class, $method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs($project, $args);
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
