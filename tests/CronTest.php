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

    public function testCronCommandHandleStopSignalMarksWorkerToExitAfterCurrentIteration(): void
    {
        $command = new TestableCronCommand();

        $this->assertFalse($command->shouldStopAfterCurrentIteration());

        $command->handleStopSignal(15);

        $this->assertTrue($command->shouldStopAfterCurrentIteration());
    }

    public function testCronCommandSleepInterruptiblyReturnsImmediatelyWhenStopWasRequested(): void
    {
        $command = new TestableCronCommand();
        $command->handleStopSignal(15);

        $start = microtime(true);
        $command->callSleepInterruptibly(1.0);
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(0.1, $elapsed);
    }

    public function testCronCommandCollectGarbageCanBeCalledExplicitly(): void
    {
        $command = new TestableCronCommand();

        $command->callCollectGarbage();

        $this->assertTrue(true);
    }

    public function testCronCommandResolveWorkerLimitsSupportsForeverAndThresholdOptions(): void
    {
        $_ENV['CRON_MAX_DURATION'] = '900';
        $_ENV['CRON_MAX_JOBS'] = '25';
        $_ENV['CRON_MAX_MEMORY_MB'] = '64';

        $command = new TestableCronCommand();
        $limits = $command->callResolveWorkerLimits([
            'forever' => true,
            'max-jobs' => '100',
            'max-memory-mb' => '128',
        ]);

        $this->assertSame(0, $limits['maxDuration']);
        $this->assertSame(100, $limits['maxJobs']);
        $this->assertSame(128 * 1024 * 1024, $limits['maxMemoryBytes']);
    }

    public function testCronCommandResolveWorkerLimitsRejectsInvalidNumericOptions(): void
    {
        $command = new TestableCronCommand();

        $this->expectException(NimbleException::class);
        $this->expectExceptionMessage('Invalid value for max-jobs. Expected a non-negative integer.');
        $command->callResolveWorkerLimits(['max-jobs' => '-1']);
    }

    public function testCronCommandResolveExitLimitMessageDetectsDurationAndJobLimits(): void
    {
        $command = new TestableCronCommand();

        $this->assertSame(
            'Max duration reached, exiting cron worker',
            $command->callResolveExitLimitMessage(time() - 10, 0, [
                'maxDuration' => 5,
                'maxJobs' => 0,
                'maxMemoryBytes' => 0,
            ])
        );

        $this->assertSame(
            'Max jobs reached, exiting cron worker',
            $command->callResolveExitLimitMessage(time(), 3, [
                'maxDuration' => 0,
                'maxJobs' => 3,
                'maxMemoryBytes' => 0,
            ])
        );
    }

    public function testCronCommandResolveMemoryLimitMessageDetectsExceededLimit(): void
    {
        $command = new TestableCronCommand();

        $this->assertSame(
            'Max memory reached, exiting cron worker',
            $command->callResolveMemoryLimitMessage(1024)
        );
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

class TestableCronCommand extends CronCommand
{
    public function shouldStopAfterCurrentIteration(): bool
    {
        return $this->shouldStopAfterCurrentIteration;
    }

    public function callSleepInterruptibly(float $seconds): void
    {
        $this->sleepInterruptibly($seconds);
    }

    public function callCollectGarbage(): void
    {
        $this->collectGarbage();
    }

    public function callResolveWorkerLimits(array $options): array
    {
        return $this->resolveWorkerLimits($options);
    }

    public function callResolveExitLimitMessage(int $startTime, int $jobsProcessed, array $limits): ?string
    {
        return $this->resolveExitLimitMessage($startTime, $jobsProcessed, $limits);
    }

    public function callResolveMemoryLimitMessage(int $maxMemoryBytes): ?string
    {
        return $this->resolveMemoryLimitMessage($maxMemoryBytes);
    }
}
