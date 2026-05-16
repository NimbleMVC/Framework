<?php

use NimblePHP\Framework\Event\AbstractEvent;
use NimblePHP\Framework\Event\EventDispatcher;
use PHPUnit\Framework\TestCase;

class EventDispatcherTest extends TestCase
{
    private EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
    }

    public function testDispatchesCustomDomainEventToRegisteredListenersInPriorityOrder(): void
    {
        $listener = new TestAfterTaskAddListener();

        $this->dispatcher->addListener(AfterTaskAdd::class, function (AfterTaskAdd $event): void {
            $event->trace[] = 'low';
        }, 10);
        $this->dispatcher->addListener(AfterTaskAdd::class, $listener, 50);
        $this->dispatcher->addListener(AfterTaskAdd::class, TestInvokableAfterTaskAddListener::class, 100);

        /** @var AfterTaskAdd $event */
        $event = $this->dispatcher->dispatch(new AfterTaskAdd(15, 'Write docs'));

        $this->assertSame(['high', 'mid', 'low'], $event->trace);
        $this->assertSame([15], $listener->handledIds);
    }

    public function testDispatchStopsPropagationWhenEventIsStopped(): void
    {
        $this->dispatcher->addListener(AfterTaskAdd::class, function (AfterTaskAdd $event): void {
            $event->trace[] = 'first';
            $event->stopPropagation();
        }, 100);
        $this->dispatcher->addListener(AfterTaskAdd::class, function (AfterTaskAdd $event): void {
            $event->trace[] = 'second';
        }, 10);

        /** @var AfterTaskAdd $event */
        $event = $this->dispatcher->dispatch(new AfterTaskAdd(1, 'Blocked'));

        $this->assertSame(['first'], $event->trace);
    }

    public function testClearRemovesRegisteredListeners(): void
    {
        $this->dispatcher->addListener(AfterTaskAdd::class, function (AfterTaskAdd $event): void {
            $event->trace[] = 'listener';
        });
        $this->dispatcher->clear();

        /** @var AfterTaskAdd $event */
        $event = $this->dispatcher->dispatch(new AfterTaskAdd(7, 'Nothing'));

        $this->assertSame([], $event->trace);
        $this->assertSame([], $this->dispatcher->getListeners());
    }

    public function testDispatchAlsoInvokesListenersRegisteredForParentEventClass(): void
    {
        $this->dispatcher->addListener(AbstractEvent::class, function (AbstractEvent $event): void {
            if ($event instanceof AfterTaskAdd) {
                $event->trace[] = 'parent';
            }
        }, 20);
        $this->dispatcher->addListener(AfterTaskAdd::class, function (AfterTaskAdd $event): void {
            $event->trace[] = 'child';
        }, 10);

        /** @var AfterTaskAdd $event */
        $event = $this->dispatcher->dispatch(new AfterTaskAdd(3, 'Inherited'));

        $this->assertSame(['parent', 'child'], $event->trace);
    }

    public function testDispatchSupportsClassStringHandleListener(): void
    {
        $this->dispatcher->addListener(AfterTaskAdd::class, TestHandleAfterTaskAddListener::class, 25);

        /** @var AfterTaskAdd $event */
        $event = $this->dispatcher->dispatch(new AfterTaskAdd(4, 'Handle'));

        $this->assertSame(['handle-class'], $event->trace);
    }

    public function testGetListenersReturnsPrioritySortedMetadata(): void
    {
        $this->dispatcher->addListener(AfterTaskAdd::class, fn (AfterTaskAdd $event) => null, 5);
        $this->dispatcher->addListener(AbstractEvent::class, TestHandleAfterTaskAddListener::class, 50);
        $this->dispatcher->addListener(AfterTaskAdd::class, new TestAfterTaskAddListener(), 10);

        $listeners = $this->dispatcher->getListeners();

        $this->assertSame([50, 10, 5], array_column($listeners, 'priority'));
        $this->assertSame([
            AbstractEvent::class,
            AfterTaskAdd::class,
            AfterTaskAdd::class,
        ], array_column($listeners, 'event'));
    }

    public function testDispatchThrowsForInvalidListenerDefinition(): void
    {
        $this->dispatcher->addListener(AfterTaskAdd::class, 'UnknownListenerClass');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid event listener');

        $this->dispatcher->dispatch(new AfterTaskAdd(2, 'Broken'));
    }
}

class AfterTaskAdd extends AbstractEvent
{
    public array $trace = [];

    public function __construct(
        public int $taskId,
        public string $title
    ) {
    }
}

class TestAfterTaskAddListener
{
    public array $handledIds = [];

    public function handle(AfterTaskAdd $event): void
    {
        $this->handledIds[] = $event->taskId;
        $event->trace[] = 'mid';
    }
}

class TestInvokableAfterTaskAddListener
{
    public function __invoke(AfterTaskAdd $event): void
    {
        $event->trace[] = 'high';
    }
}

class TestHandleAfterTaskAddListener
{
    public function handle(AfterTaskAdd $event): void
    {
        $event->trace[] = 'handle-class';
    }
}
