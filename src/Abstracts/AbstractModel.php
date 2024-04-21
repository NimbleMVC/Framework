<?php

namespace Nimblephp\framework\Abstracts;

use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use krzysztofzylka\DatabaseManager\Table;
use Nimblephp\framework\Config;
use Nimblephp\framework\Exception\DatabaseException;
use Nimblephp\framework\Interfaces\ModelInterface;

/**
 * Abstract model
 */
abstract class AbstractModel implements ModelInterface
{

    /**
     * Use table string / false (no) / null (auto)
     * @var string|false|null
     */
    public null|string|false $useTable = null;

    /**
     * Model name
     * @var string
     */
    public string $name;

    /**
     * Table instance
     * @var Table
     */
    protected Table $table;

    /**
     * Actual element id
     * @var ?int
     */
    protected ?int $id = null;

    /**
     * Create element
     * @param array $data
     * @return bool
     * @throws DatabaseException
     */
    public function create(array $data): bool
    {
        if (!Config::get('DATABASE') || $this->useTable === false) {
            throw new DatabaseException('Database is disabled');
        }

        try {
            $create = $this->table->insert($data);
            $this->setId($this->table->getId());

            return $create;
        } catch (DatabaseManagerException $exception) {
            throw new DatabaseException($exception->getHiddenMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Read element
     * @param array|null $condition
     * @param array|null $columns
     * @param string|null $orderBy
     * @return array
     * @throws DatabaseException
     */
    public function read(?array $condition = null, ?array $columns = null, ?string $orderBy = null): array
    {
        if (!Config::get('DATABASE') || $this->useTable === false) {
            throw new DatabaseException('Database is disabled');
        }

        try {
            return $this->table->find($condition, $columns, $orderBy);
        } catch (DatabaseManagerException $exception) {
            throw new DatabaseException($exception->getHiddenMessage(), $exception->getCode(), $exception);
        }
    }

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
    public function readAll(?array $condition = null, ?array $columns = null, ?string $orderBy = null, ?string $limit = null, ?string $groupBy = null): array
    {
        if (!Config::get('DATABASE') || $this->useTable === false) {
            throw new DatabaseException('Database is disabled');
        }

        try {
            return $this->table->findAll($condition, $columns, $orderBy, $limit, $groupBy);
        } catch (DatabaseManagerException $exception) {
            throw new DatabaseException($exception->getHiddenMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Update element
     * @param array $data
     * @return bool
     * @throws DatabaseException
     */
    public function update(array $data): bool
    {
        if (!Config::get('DATABASE') || $this->useTable === false) {
            throw new DatabaseException('Database is disabled');
        } elseif (is_null($this->getId())) {
            return false;
        }

        try {
            return $this->table->update($data);
        } catch (DatabaseManagerException $exception) {
            throw new DatabaseException($exception->getHiddenMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Delete element by ID
     * @return bool
     * @throws DatabaseException
     */
    public function delete(): bool
    {
        if (!Config::get('DATABASE') || $this->useTable === false) {
            throw new DatabaseException('Database is disabled');
        } elseif (is_null($this->getId())) {
            return false;
        }

        try {
            return $this->table->delete($this->getId());
        } catch (DatabaseManagerException $exception) {
            throw new DatabaseException($exception->getHiddenMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Get element id
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set element id
     * @param int|null $id
     * @return ModelInterface
     */
    public function setId(?int $id = null): ModelInterface
    {
        $this->table->setId($id);
        $this->id = $id;

        return $this;
    }

    /**
     * Prepare table instance
     * @return void
     */
    public function prepareTableInstance(): void
    {
        if (!Config::get('DATABASE')) {
            return;
        }

        if ($this->useTable === false) {
            return;
        } elseif (is_null($this->useTable)) {
            $this->useTable = $this->name;
        }

        $this->table = new Table($this->useTable);
    }

}