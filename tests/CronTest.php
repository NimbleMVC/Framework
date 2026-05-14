<?php

use krzysztofzylka\DatabaseManager\DatabaseLock;
use krzysztofzylka\DatabaseManager\Table;
use NimblePHP\Framework\Abstracts\AbstractController;
use NimblePHP\Framework\CLI\Commands\Cron as CronCommand;
use NimblePHP\Framework\Cron;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Interfaces\ControllerInterface;
use PHPUnit\Framework\TestCase;

class CronTest extends TestCase
{
    public function testAddJobRejectsUnsupportedType(): void
    {
        $cron = (new ReflectionClass(Cron::class))->newInstanceWithoutConstructor();

        $this->expectException(NimbleException::class);
        $this->expectExceptionMessage('Type must be "model"');
        $cron->addJob('service', 'name', 'action');
    }

    public function testRunJobReturnsFalseWhenQueueIsEmpty(): void
    {
        $table = $this->createMock(Table::class);
        $table->method('getName')->willReturn('cron_job');
        $table->expects($this->once())->method('find')->willReturn([]);

        $lock = $this->createMock(DatabaseLock::class);
        $lock->expects($this->once())->method('lock')->with('cron_run_jobs');
        $lock->expects($this->once())->method('unlock')->with('cron_run_jobs');

        $cron = $this->buildCronInstance($table, $lock);

        $this->assertFalse($cron->runJob());
    }

    public function testRunJobDeletesExpiredJob(): void
    {
        $table = $this->createMock(Table::class);
        $table->method('getName')->willReturn('cron_job');
        $table->expects($this->once())->method('find')->willReturn([
            'cron_job' => [
                'id' => 12,
                'date_expiration' => date('Y-m-d H:i:s', time() - 60),
            ],
        ]);
        $table->expects($this->once())->method('delete')->with(12);

        $lock = $this->createMock(DatabaseLock::class);
        $lock->expects($this->once())->method('lock')->with('cron_run_jobs');
        $lock->expects($this->once())->method('unlock')->with('cron_run_jobs');

        $cron = $this->buildCronInstance($table, $lock);

        $this->assertTrue($cron->runJob());
    }

    public function testUpdateStatusRejectsUnsupportedStatus(): void
    {
        $cron = (new ReflectionClass(Cron::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(Cron::class, 'updateStatus');
        $method->setAccessible(true);

        $this->expectException(NimbleException::class);
        $this->expectExceptionMessage('Status must be "new", "processing", "runned" or "failed"');
        $method->invoke($cron, 1, 'done');
    }

    public function testGetControllerBuildsDefaultCronController(): void
    {
        $cron = (new ReflectionClass(Cron::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(Cron::class, 'getController');
        $method->setAccessible(true);

        $controller = $method->invoke($cron);

        $this->assertInstanceOf(AbstractController::class, $controller);
        $this->assertSame('cronjob', $controller->name);
        $this->assertSame('cronjob', $controller->action);
    }

    public function testCronCommandResolveControllerHandlesValidAndInvalidInputs(): void
    {
        $command = new CronCommand();
        $method = new ReflectionMethod(CronCommand::class, 'resolveController');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($command, null));
        $this->assertNull($method->invoke($command, ''));

        $instance = new CronCommandTestController();
        $this->assertSame($instance, $method->invoke($command, $instance));

        $resolved = $method->invoke($command, CronCommandTestController::class);
        $this->assertInstanceOf(CronCommandTestController::class, $resolved);
        $this->assertTrue($resolved->afterConstructCalled);

        $this->expectException(NimbleException::class);
        $this->expectExceptionMessage('CRON_CONTROLLER class "MissingController" does not exist');
        $method->invoke($command, 'MissingController');
    }

    private function buildCronInstance(Table $table, DatabaseLock $lock): Cron
    {
        $cron = (new ReflectionClass(Cron::class))->newInstanceWithoutConstructor();

        $tableProperty = new ReflectionProperty(Cron::class, 'table');
        $tableProperty->setAccessible(true);
        $tableProperty->setValue($cron, $table);

        $lockProperty = new ReflectionProperty(Cron::class, 'databaseLock');
        $lockProperty->setAccessible(true);
        $lockProperty->setValue($cron, $lock);

        return $cron;
    }
}

class CronCommandTestController implements ControllerInterface
{
    public bool $afterConstructCalled = false;

    public function loadModel(string $name): object
    {
        return new stdClass();
    }

    public function log(string $message, string $level = 'INFO', array $content = []): bool
    {
        return true;
    }

    public function afterConstruct(): void
    {
        $this->afterConstructCalled = true;
    }
}
