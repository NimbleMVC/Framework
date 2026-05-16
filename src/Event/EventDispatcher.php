<?php

namespace NimblePHP\Framework\Event;

use RuntimeException;

/**
 * Register and dispatch typed framework or application events.
 */
class EventDispatcher
{

    /**
     * @var array<string, array<int, array<int, callable|object|string>>>
     */
    protected array $listeners = [];

    /**
     * @var array<int, array{event: string, listener: callable|object|string, priority: int}>
     */
    protected array $listenerList = [];

    /**
     * Register a listener for the given event class.
     *
     * Higher priority listeners run first. Listener can be a closure,
     * invokable object, object with handle() method, or class-string.
     *
     * @param string $eventClass
     * @param mixed $listener
     * @param int $priority
     * @return void
     */
    public function addListener(string $eventClass, mixed $listener, int $priority = 0): void
    {
        $this->listeners[$eventClass][$priority][] = $listener;
        $this->listenerList[] = [
            'event' => $eventClass,
            'listener' => $listener,
            'priority' => $priority,
        ];
    }

    /**
     * Dispatch an event to all matching listeners.
     *
     * @param object $event
     * @return object
     */
    public function dispatch(object $event): object
    {
        foreach ($this->getListenersForEvent($event) as $listener) {
            $this->invokeListener($listener, $event);

            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }
        }

        return $event;
    }

    /**
     * Remove all registered listeners.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->listeners = [];
        $this->listenerList = [];
    }

    /**
     * Return the flat listener registry sorted by priority descending.
     *
     * @return array
     */
    public function getListeners(): array
    {
        usort($this->listenerList, fn(array $a, array $b): int => $b['priority'] <=> $a['priority']);

        return $this->listenerList;
    }

    /**
     * Resolve listeners that should receive the provided event instance.
     *
     * @param object $event
     * @return array
     */
    protected function getListenersForEvent(object $event): array
    {
        $matched = [];

        foreach ($this->listeners as $eventClass => $listenersByPriority) {
            if (!$event instanceof $eventClass) {
                continue;
            }

            foreach ($listenersByPriority as $priority => $listeners) {
                foreach ($listeners as $listener) {
                    $matched[$priority][] = $listener;
                }
            }
        }

        if (empty($matched)) {
            return [];
        }

        krsort($matched);

        return array_merge(...array_values($matched));
    }

    /**
     * Invoke a listener using the supported listener conventions.
     *
     * @param mixed $listener
     * @param object $event
     * @return void
     */
    protected function invokeListener(mixed $listener, object $event): void
    {
        if (is_callable($listener)) {
            $listener($event);
            return;
        }

        if (is_string($listener) && class_exists($listener)) {
            $listener = new $listener();
        }

        if (is_object($listener) && method_exists($listener, '__invoke')) {
            $listener($event);
            return;
        }

        if (is_object($listener) && method_exists($listener, 'handle')) {
            $listener->handle($event);
            return;
        }

        throw new RuntimeException('Invalid event listener');
    }

}
