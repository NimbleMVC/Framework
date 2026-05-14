<?php

use NimblePHP\Framework\CLI\Console;
use NimblePHP\Framework\CLI\Input;
use NimblePHP\Framework\CLI\Output;
use NimblePHP\Framework\CLI\Attributes\ConsoleCommand;
use PHPUnit\Framework\TestCase;

class ConsoleTest extends TestCase
{
    protected function setUp(): void
    {
        $reflection = new ReflectionClass(Console::class);
        $commandsProperty = $reflection->getProperty('commands');
        $commandsProperty->setAccessible(true);
        $commandsProperty->setValue(null, []);
        $_SERVER['argv'][0] = 'vendor/bin/nimble';
    }

    public function testResolveOverviewGroupMapsKnownAndCustomPrefixes(): void
    {
        $this->assertSame('Core', $this->invokeConsoleMethod('resolveOverviewGroup', 'serve'));
        $this->assertSame('Logs', $this->invokeConsoleMethod('resolveOverviewGroup', 'logs:tail'));
        $this->assertSame('Custom tool', $this->invokeConsoleMethod('resolveOverviewGroup', 'custom-tool:run'));
    }

    public function testRegisterCommandClassRegistersClassAndMethodCommands(): void
    {
        $this->invokeConsoleMethod('registerCommandClass', ConsoleTestCommand::class);

        $commands = $this->getRegisteredCommands();

        $this->assertArrayHasKey('test:class', $commands);
        $this->assertArrayHasKey('test:method', $commands);
        $this->assertSame('class', $commands['test:class']['mode']);
        $this->assertSame('handle', $commands['test:class']['method']);
        $this->assertSame('method', $commands['test:method']['mode']);
        $this->assertSame('runMethod', $commands['test:method']['method']);
    }

    public function testResolveArgumentsMetadataIgnoresFrameworkSpecificParameters(): void
    {
        $metadata = $this->invokeConsoleMethod('resolveArgumentsMetadata', [
            'class' => ConsoleTestCommand::class,
            'method' => 'runMethod',
            'arguments' => [],
        ]);

        $this->assertSame([
            [
                'name' => 'name',
                'description' => 'The name argument.',
                'required' => true,
                'default' => null,
            ],
            [
                'name' => 'times',
                'description' => 'The times argument.',
                'required' => false,
                'default' => '1',
            ],
        ], $metadata);
    }

    public function testResolveOptionsMetadataAppendsHelpFlags(): void
    {
        $metadata = $this->invokeConsoleMethod('resolveOptionsMetadata', [
            'options' => [
                ['name' => '--force', 'description' => 'Force execution.'],
            ],
        ]);

        $names = array_column($metadata, 'name');

        $this->assertContains('--force', $names);
        $this->assertContains('--help', $names);
        $this->assertContains('-h', $names);
    }

    public function testGenerateUsageIncludesPositionalOptionalAndOptionsPlaceholder(): void
    {
        $usage = $this->invokeConsoleMethod('generateUsage', 'test:method', [
            'class' => ConsoleTestCommand::class,
            'method' => 'runMethod',
        ]);

        $this->assertSame('php vendor/bin/nimble test:method <name> [times] [--option=value]', $usage);
    }

    public function testResolveMethodParametersInjectsInputOutputArrayAndPositionals(): void
    {
        $reflectionMethod = new ReflectionMethod(ConsoleTestCommand::class, 'runMethod');
        $input = new Input('test:method', ['john'], [0 => 'john', 'force' => true]);
        $output = new Output();

        $arguments = $this->invokeConsoleMethod('resolveMethodParameters', $reflectionMethod, $input, $output);

        $this->assertSame($input, $arguments[0]);
        $this->assertSame($output, $arguments[1]);
        $this->assertSame('john', $arguments[2]);
        $this->assertSame([0 => 'john', 'force' => true], $arguments[3]);
    }

    private function invokeConsoleMethod(string $method, mixed ...$args): mixed
    {
        $reflectionMethod = new ReflectionMethod(Console::class, $method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs(null, $args);
    }

    private function getRegisteredCommands(): array
    {
        $reflection = new ReflectionClass(Console::class);
        $commandsProperty = $reflection->getProperty('commands');
        $commandsProperty->setAccessible(true);

        return $commandsProperty->getValue();
    }
}

#[ConsoleCommand(command: 'test:class', description: 'Class command')]
class ConsoleTestCommand
{
    public function handle(Input $input, Output $output): int
    {
        return 0;
    }

    #[ConsoleCommand(command: 'test:method', description: 'Method command')]
    public function runMethod(Input $input, Output $output, string $name, int $times = 1, array $options = []): int
    {
        return $times;
    }
}
