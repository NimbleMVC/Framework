<?php

namespace Nimblephp\framework\Interfaces;

use krzysztofzylka\DatabaseManager\Table;
use Nimblephp\framework\Exception\DatabaseException;

/**
 * Model interface
 */
interface ModelInterface
{

    /**
     * Prepare table instance
     * @return void
     */
    public function prepareTableInstance(): void;

    /**
     * Create element
     * @param array $data
     * @return bool
     * @throws DatabaseException
     */
    public function create(array $data): bool;

    /**
     * Read element
     * @param array|null $condition
     * @param array|null $columns
     * @param string|null $orderBy
     * @return array
     * @throws DatabaseException
     */
    public function read(?array $condition = null, ?array $columns = null, ?string $orderBy = null): array;

    /**
     * Read multiple element
     * @param array|null $condition
     * @param array|null $columns
     * @param string|null $orderBy
     * @param string|null $limit
     * @param string|null $groupBy
     * @return array
     * @throws DatabaseException
     */
    public function readAll(?array $condition = null, ?array $columns = null, ?string $orderBy = null, ?string $limit = null, ?string $groupBy = null): array;

    /**
     * Update element
     * @param array $data
     * @return bool
     * @throws DatabaseException
     */
    public function update(array $data): bool;

    /**
     * Delete element by ID
     * @return bool
     * @throws DatabaseException
     */
    public function delete(): bool;

    /**
     * Get element id
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Set element id
     * @param int|null $id
     * @return ModelInterface
     */
    public function setId(?int $id = null): self;

    /**
     * Count elements
     * @param array|null $condition
     * @param string|null $groupBy
     * @return int
     * @throws DatabaseException
     */
    public function count(?array $condition = null, ?string $groupBy = null): int;

    /**
     * Count elements
     * @param array|null $condition
     * @return int
     * @throws DatabaseException
     */
    public function isset(?array $condition = null): int;

    /**
     * Query
     * @param string $sql
     * @return array
     * @throws DatabaseException
     */
    public function query(string $sql): array;

    /**
     * Get table instance
     * @return Table
     */
    public function getTableInstance(): Table;

    /**
     * Create log
     * @param string $message
     * @param string $level
     * @param array $content
     * @return bool
     */
    public function log(string $message, string $level = 'INFO', array $content = []): bool;

    /**
     * After construct method
     * @return void
     */
    public function afterConstruct(): void;

}