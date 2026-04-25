<?php

use NimblePHP\Framework\Attributes\Cron\Cron;
use PHPUnit\Framework\TestCase;

class CronAttributeTest extends TestCase
{
    public function testConstructorExposesRunAfterAndExpirationDates(): void
    {
        $cron = new Cron(
            '* * * * *',
            \NimblePHP\Framework\Cron::PRIORITY_HIGH,
            ['foo' => 'bar'],
            '2026-04-25 10:00:00',
            '2026-04-26 10:00:00'
        );

        $this->assertSame('* * * * *', $cron->time);
        $this->assertSame(\NimblePHP\Framework\Cron::PRIORITY_HIGH, $cron->priority);
        $this->assertSame(['foo' => 'bar'], $cron->parameters);
        $this->assertSame('2026-04-25 10:00:00', $cron->runAfterDate);
        $this->assertSame('2026-04-26 10:00:00', $cron->expirationDate);
    }

    public function testReflectionInstantiatesOptionalDatesFromAttribute(): void
    {
        $method = new ReflectionMethod(CronAttributeTestFixture::class, 'handle');
        $attribute = $method->getAttributes(Cron::class)[0];

        /** @var Cron $cron */
        $cron = $attribute->newInstance();

        $this->assertSame('*/5 * * * *', $cron->time);
        $this->assertSame(['job' => 'cleanup'], $cron->parameters);
        $this->assertSame('2026-04-25 12:00:00', $cron->runAfterDate);
        $this->assertSame('2026-04-30 12:00:00', $cron->expirationDate);
    }
}

class CronAttributeTestFixture
{
    #[Cron(
        '*/5 * * * *',
        parameters: ['job' => 'cleanup'],
        runAfterDate: '2026-04-25 12:00:00',
        expirationDate: '2026-04-30 12:00:00'
    )]
    public function handle(): void
    {
    }
}
