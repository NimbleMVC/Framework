<?php

namespace NimblePHP\Framework\Interfaces;

use Exception;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use krzysztofzylka\DatabaseManager\Table;

/**
 * Model interface
 */
interface ORMModelInterface
{

    /**
     * Construct ORM object
     * @param array $data
     */
    public function __construct(array $data = []);

    /**
     * Get table name
     * @return string
     */
    public static function getTableName(): string;

    /**
     * Get table instance
     * @return Table
     */
    public static function getTableInstance(): Table;

    /**
     * Get columns
     * @return array
     */
    public static function getColumns(): array;

    /**
     * Find element
     * @param array $conditions
     * @return static|null
     * @throws DatabaseManagerException
     */
    public static function read(array $conditions = []): ?static;

    /**
     * Get all elements
     * @param array $conditions
     * @return array
     * @throws DatabaseManagerException
     */
    public static function readAll(array $conditions = []): array;

    /**
     * Insert or update data
     * @return bool
     * @throws DatabaseManagerException
     */
    public function save(): bool;

    /**
     * Delete
     * @return bool
     * @throws DatabaseManagerException
     * @throws Exception
     */
    public function delete(): bool;

    /**
     * Get array
     * @return array
     */
    public function toArray(): array;

}