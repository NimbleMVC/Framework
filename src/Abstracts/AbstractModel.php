<?php

namespace Nimblephp\framework\Abstracts;

use Exception;
use krzysztofzylka\DatabaseManager\Condition;
use krzysztofzylka\DatabaseManager\Enum\BindType;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use krzysztofzylka\DatabaseManager\Table;
use Nimblephp\framework\Exception\DatabaseException;
use Nimblephp\framework\Interfaces\ControllerInterface;
use Nimblephp\framework\Interfaces\ModelInterface;
use Nimblephp\framework\Log;
use Nimblephp\framework\Traits\LoadModelTrait;

/**
 * Abstract model
 */
abstract class AbstractModel implements ModelInterface
{

    use LoadModelTrait;

    /**
     * Use table string (table name) / false (no) / null (auto)
     * @var string|false|null
     */
    public null|string|false $useTable = null;

    /**
     * Model name
     * @var string
     */
    public string $name;

    /**
     * Controller instance
     * @var ControllerInterface
     */
    public ControllerInterface $controller;

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
     * After construct method
     * @return void
     * @action disabled
     */
    public function afterConstruct(): void
    {
    }

    /**
     * Create element
     * @param array $data
     * @return bool
     * @throws DatabaseException
     */
    public function create(array $data): bool
    {
        if (!$_ENV['DATABASE'] || $this->useTable === false) {
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
        if (!$_ENV['DATABASE'] || $this->useTable === false) {
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
        if (!$_ENV['DATABASE'] || $this->useTable === false) {
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
        if (!$_ENV['DATABASE'] || $this->useTable === false) {
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
        if (!$_ENV['DATABASE'] || $this->useTable === false) {
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
        if (!$_ENV['DATABASE']) {
            return;
        }

        if ($this->useTable === false) {
            return;
        } elseif (is_null($this->useTable)) {
            $this->useTable = $this->name;
        }

        $this->table = new Table($this->useTable);
    }

    /**
     * Count elements
     * @param array|null $condition
     * @param string|null $groupBy
     * @return int
     * @throws DatabaseException
     */
    public function count(?array $condition = null, ?string $groupBy = null): int
    {
        if (!$_ENV['DATABASE'] || $this->useTable === false) {
            throw new DatabaseException('Database is disabled');
        }

        try {
            return $this->table->findCount($condition, $groupBy);
        } catch (DatabaseManagerException $exception) {
            throw new DatabaseException($exception->getHiddenMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Isset element
     * @param array|null $condition
     * @return int
     * @throws DatabaseException
     */
    public function isset(?array $condition = null): int
    {
        if (!$_ENV['DATABASE'] || $this->useTable === false) {
            throw new DatabaseException('Database is disabled');
        }

        try {
            return $this->table->findIsset($condition);
        } catch (DatabaseManagerException $exception) {
            throw new DatabaseException($exception->getHiddenMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Query
     * @param string $sql
     * @return array
     * @throws DatabaseException
     */
    public function query(string $sql): array
    {
        if (!$_ENV['DATABASE'] || $this->useTable === false) {
            throw new DatabaseException('Database is disabled');
        }

        try {
            return $this->table->query($sql);
        } catch (DatabaseManagerException $exception) {
            throw new DatabaseException($exception->getHiddenMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Get table instance
     * @return Table
     */
    public function getTableInstance(): Table
    {
        return $this->table;
    }

    /**
     * Create logs
     * @param string $message
     * @param string $level
     * @param array $content
     * @return bool
     * @throws Exception
     */
    public function log(string $message, string $level = 'INFO', array $content = []): bool
    {
        return Log::log($message, $level, $content);
    }

    /**
     * Bind table
     * @param array|BindType $bind
     * @param string|null $tableName
     * @param ?string $primaryKey
     * @param ?string $foreignKey
     * @param array|Condition|null $condition
     * @return $this
     */
    public function bind(
        BindType|array $bind,
        ?string $tableName = null,
        ?string $primaryKey = null,
        ?string $foreignKey = null,
        null|array|Condition $condition = null
    ): self
    {
        $this->table->bind($bind, $tableName, $primaryKey, $foreignKey, $condition);

        return $this;
    }

    /**
     * Magic get method
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function __get(string $name)
    {
        $loadModel = $this->__getModel($name);

        if (!is_null($loadModel)) {
            return $loadModel;
        }

        $className = $this::class;
        throw new Exception("Undefined property: {$className}::{$name}", 2);
    }

}