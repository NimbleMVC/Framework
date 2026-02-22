<?php

namespace NimblePHP\Framework\Container;

use NimblePHP\Framework\Interfaces\ContainerInterface;
use NimblePHP\Framework\Kernel;
use RuntimeException;

/**
 * Service container
 */
class ServiceContainer extends ContainerBase implements ContainerInterface
{

    /**
     * Services
     * @var array
     */
    protected array $services = [];

    /**
     * Factories
     * @var array
     */
    protected array $factories = [];

    /**
     * Aliases
     * @var array
     */
    protected array $aliases = [];

    /**
     * Resolved services
     * @var array
     */
    protected array $resolved = [];

    /**
     * Resolving services
     * @var array
     */
    protected array $resolving = [];

    /**
     * Service stats
     */
    protected array $stats = [];

    /**
     * Set a service in the container
     * @param string $id
     * @param mixed $service
     * @param bool $secured
     * @return void
     */
    public function set(string $id, mixed $service, bool $secured = true): void
    {
        if ($secured && array_key_exists($id, $this->services) && is_object($service) && $this->services[$id]::class === $service::class) {
            return;
        }

        $this->increaseStats($id, 'set');
        Kernel::$middlewareManager->runHook('serviceSet', [$id, $service]);

        if (empty($id)) {
            throw new RuntimeException('Service ID cannot be empty');
        }

        $this->services[$id] = $service;
        unset($this->resolved[$id]);
    }

    /**
     * Set a factory in the container
     * @param string $id
     * @param callable $factory
     * @return void
     */
    public function setFactory(string $id, callable $factory): void
    {
        if (empty($id)) {
            throw new RuntimeException('Service ID cannot be empty');
        }

        $this->factories[$id] = $factory;
        unset($this->resolved[$id]);
    }

    /**
     * Get a service from the container
     * @param string $id
     * @return mixed
     */
    public function get(string $id): mixed
    {
        $this->increaseStats($id, 'get');
        Kernel::$middlewareManager->runHook('serviceGet', [$id]);

        if (empty($id)) {
            throw new RuntimeException('Service ID cannot be empty');
        }

        $resolvedId = $this->resolveAlias($id);

        if (isset($this->resolved[$resolvedId])) {
            return $this->resolved[$resolvedId];
        }

        if (isset($this->resolving[$resolvedId])) {
            throw new RuntimeException("Circular dependency detected for service: $id");
        }

        $this->resolving[$resolvedId] = true;

        try {
            if (isset($this->factories[$resolvedId])) {
                return call_user_func($this->factories[$resolvedId], $this);
            } elseif (isset($this->services[$resolvedId])) {
                $service = $this->services[$resolvedId];
                $this->resolved[$resolvedId] = $service;
                return $service;
            } else {
                throw new RuntimeException("Service '$id' not found");
            }
        } finally {
            unset($this->resolving[$resolvedId]);
        }
    }

    /**
     * Check if a service exists in the container
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        $this->increaseStats($id, 'has');
        Kernel::$middlewareManager->runHook('serviceHas', [$id]);

        if (empty($id)) {
            return false;
        }

        $resolvedId = $this->resolveAlias($id);
        return isset($this->services[$resolvedId]) || isset($this->factories[$resolvedId]);
    }

    /**
     * Remove a service from the container
     * @param string $id
     * @return void
     */
    public function remove(string $id): void
    {
        $this->increaseStats($id, 'remove');
        Kernel::$middlewareManager->runHook('serviceRemove', [$id]);

        if (empty($id)) {
            return;
        }

        $resolvedId = $this->resolveAlias($id);
        unset($this->services[$resolvedId], $this->factories[$resolvedId], $this->resolved[$resolvedId]);

        foreach ($this->aliases as $alias => $targetId) {
            if ($targetId === $resolvedId) {
                unset($this->aliases[$alias]);
            }
        }
    }

    /**
     * Set an alias for a service
     * @param string $alias
     * @param string $id
     * @return void
     */
    public function setAlias(string $alias, string $id): void
    {
        $this->increaseStats($id, 'setAlias');

        if (empty($alias) || empty($id)) {
            throw new RuntimeException('Alias and target ID cannot be empty');
        }

        if ($alias === $id) {
            throw new RuntimeException('Alias cannot reference itself');
        }

        $this->aliases[$alias] = $id;
    }

    /**
     * Resolve an alias
     * @param string $id
     * @return string
     */
    protected function resolveAlias(string $id): string
    {
        return $this->aliases[$id] ?? $id;
    }

    /**
     * Get resolved services
     * @return array
     */
    public function getResolvedServices(): array
    {
        return array_keys($this->resolved);
    }

    /**
     * Get registered services
     * @return array
     */
    public function getRegisteredServices(): array
    {
        return array_merge(array_keys($this->services), array_keys($this->factories));
    }

    /**
     * Clear the container
     * @return void
     */
    public function clear(): void
    {
        $this->services = [];
        $this->factories = [];
        $this->aliases = [];
        $this->resolved = [];
        $this->resolving = [];
    }

    /**
     * @param string $id
     * @param string $type
     * @return void
     */
    private function increaseStats(string $id, string $type): void
    {
        if (!array_key_exists($id, $this->stats)) {
            $this->stats[$id] = [
                'get' => 0,
                'set' => 0,
                'has' => 0,
                'remove' => 0,
                'setAlias' => 0
            ];
        }

        $this->stats[$id][$type]++;
    }

}