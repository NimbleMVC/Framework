<?php

use NimblePHP\Framework\CLI\Input;
use PHPUnit\Framework\TestCase;

class InputTest extends TestCase
{
    public function testSeparatesPositionalsOptionsAndNamedArguments(): void
    {
        $input = new Input(
            command: 'module:create',
            rawArguments: ['blog', '--force', '--path=src'],
            parsedArguments: [0 => 'blog', 'force' => true, 'path' => 'src'],
            argumentMetadata: [
                ['name' => 'name'],
            ]
        );

        $this->assertSame('module:create', $input->command());
        $this->assertSame(['blog', '--force', '--path=src'], $input->rawArguments());
        $this->assertSame([0 => 'blog', 'force' => true, 'path' => 'src'], $input->all());
        $this->assertSame(['blog'], $input->positionals());
        $this->assertSame(['name' => 'blog'], $input->arguments());
        $this->assertSame(['force' => true, 'path' => 'src'], $input->options());
    }

    public function testArgumentCanBeResolvedByIndexOrName(): void
    {
        $input = new Input(
            command: 'make:model',
            rawArguments: ['User', 'Admin'],
            parsedArguments: [0 => 'User', 1 => 'Admin'],
            argumentMetadata: [
                ['name' => 'model'],
                ['name' => 'role'],
            ]
        );

        $this->assertSame('User', $input->argument(0));
        $this->assertSame('Admin', $input->argument(1));
        $this->assertSame('User', $input->argument('model'));
        $this->assertSame('Admin', $input->argument('role'));
        $this->assertSame('fallback', $input->argument('missing', 'fallback'));
        $this->assertTrue($input->hasArgument('model'));
        $this->assertTrue($input->hasArgument(1));
        $this->assertFalse($input->hasArgument('missing'));
    }

    public function testOptionsAreNormalizedWithOrWithoutLeadingDashes(): void
    {
        $input = new Input(
            command: 'serve',
            rawArguments: ['--host=127.0.0.1', '-p=8080'],
            parsedArguments: ['host' => '127.0.0.1', 'p' => '8080'],
        );

        $this->assertSame('127.0.0.1', $input->option('host'));
        $this->assertSame('127.0.0.1', $input->option('--host'));
        $this->assertSame('8080', $input->option('-p'));
        $this->assertSame('fallback', $input->option('missing', 'fallback'));
        $this->assertTrue($input->hasOption('host'));
        $this->assertTrue($input->hasOption('--host'));
        $this->assertTrue($input->hasOption('-p'));
        $this->assertFalse($input->hasOption('missing'));
    }
}
