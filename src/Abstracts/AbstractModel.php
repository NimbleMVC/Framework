<?php

namespace NimblePHP\Framework\Abstracts;

use krzysztofzylka\DatabaseManager\Condition;
use krzysztofzylka\DatabaseManager\Enum\BindType;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use krzysztofzylka\DatabaseManager\Table;
use NimblePHP\Framework\Config;
use NimblePHP\Framework\Enums\ModelTypeEnum;
use NimblePHP\Framework\Exception\DatabaseException;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Exception\NotFoundException;
use NimblePHP\Framework\Interfaces\ControllerInterface;
use NimblePHP\Framework\Interfaces\ModelInterface;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Traits\LoadModelTrait;
use NimblePHP\Framework\Traits\LogTrait;

/**
 * Abstract model
 */
abstract class AbstractModel implements ModelInterface
{

    use LoadModelTrait;
    use LogTrait;

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
     * Global conditions
     * @var array
     */
    public array $conditions = [];

    /**
     * Model type
     * @var ModelTypeEnum
     */
    public ModelTypeEnum $modelType = ModelTypeEnum::V1;

    /**
     * After construct method
     * @return void
     * @action disabled
     */
    public function afterConstruct(): void
    {
        Kernel::$middlewareManager->runHookWithReference('afterConstructModel', $this);
    }

    /**
     * Create element
     * @param array $data
     * @return bool
     * @throws DatabaseException
     */
    public function create(array $data): bool
    {
        if (!Config::get('DATABASE', false) || $this->useTable === false) {
            throw new DatabaseException('Database is disabled');
        }

        try {
            $middlewareData = ['model' => $this, 'data' => $data, 'type' => 'create'];
            Kernel::$middlewareManager->runHookWithReference('processingModelData', $middlewareData);
            $data = $middlewareData['data'];

            $create = $this->table->insert($data);
            $this->setId($this->table->getId());

            return $create;
        } catch (DatabaseManagerException $exception) {
            throw new DatabaseException($exception->getHiddenMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Update single value
     * @param string $name
     * @param mixed $value
     * @return bool
     * @throws DatabaseException
     * @throws NimbleException
     */
    public function updateValue(string $name, mixed $value): bool
    {
        if (!Config::get('DATABASE', false) || $this->useTable === false) {
            throw new DatabaseException('Database is disabled');
        } elseif (is_null($this->getId())) {
            return false;
        }

        try {
            $data = [$name => $value];
            $middlewareData = ['model' => $this, 'data' => $data, 'type' => 'updateValue'];
            Kernel::$middlewareManager->runHookWithReference('processingModelData', $middlewareData);
            $data = $middlewareData['data'];

            return $this->table->updateValue($name, $data[$name]);
        } catch (DatabaseManagerException $exception) {
            throw new DatabaseException($exception->getHiddenMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Create or update element
     * @param array $data
     * @return bool
     * @throws DatabaseException
     */
    public function save(array $data): bool
    {
        if (!Config::get('DATABASE', false) || $this->useTable === false) {
            throw new DatabaseException('Database is disabled');
        }

        if (is_null($this->getId())) {
            return $this->create($data);
        }

        return $this->update($data);
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
        if (!Config::get('DATABASE', false) || $this->useTable === false) {
            throw new DatabaseException('Database is disabled');
        }

        try {
            $condition = $this->prepareCondition($condition);

            return $this->table->find($condition, $columns, $orderBy);
        } catch (DatabaseManagerException $exception) {
            throw new DatabaseException($exception->getHiddenMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Read element or throw NotFoundException
     * @param array|null $condition
     * @param array|null $columns
     * @param string|null $orderBy
     * @return array
     * @throws DatabaseException
     * @throws NotFoundException
     */
    public function readSecure(?array $condition = null, ?array $columns = null, ?string $orderBy = null): array
    {
        if (!Config::get('DATABASE', false) || $this->useTable === false) {
            throw new DatabaseException('Database is disabled');
        }

        $find = $this->read($condition, $columns, $orderBy);

        if (empty($find)) {
            throw new NotFoundException();
        }

        return $find;
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
        if (!Config::get('DATABASE', false) || $this->useTable === false) {
            throw new DatabaseException('Database is disabled');
        }

        try {
            $condition = $this->prepareCondition($condition);

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
        if (!Config::get('DATABASE', false) || $this->useTable === false) {
            throw new DatabaseException('Database is disabled');
        } elseif (is_null($this->getId())) {
            return false;
        }

        try {
            $middlewareData = ['model' => $this, 'data' => $data, 'type' => 'update'];
            Kernel::$middlewareManager->runHookWithReference('processingModelData', $middlewareData);
            $data = $middlewareData['data'];

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
        if (!Config::get('DATABASE', false) || $this->useTable === false) {
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
     * Delete element\s by conditions
     * @param array $conditions
     * @return bool
     * @throws DatabaseException
     */
    public function deleteByConditions(array $conditions): bool
    {
        if (!Config::get('DATABASE', false) || $this->useTable === false) {
            throw new DatabaseException('Database is disabled');
        }

        try {
            return $this->table->deleteByConditions($conditions);
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
     * @return self
     */
    public function setId(?int $id = null): self
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
        if (!Config::get('DATABASE', false)) {
            return;
        }

        if ($this->useTable === false) {
            return;
        } elseif (is_null($this->useTable)) {
            if ($this->modelType === ModelTypeEnum::V2) {
                $explodeName = explode('_', $this->name);
                $newTableName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', str_replace('Model', '', end($explodeName))));
                $this->useTable = $newTableName;
            } else {
                $this->useTable = $this->name;
            }
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
        if (!Config::get('DATABASE', false) || $this->useTable === false) {
            throw new DatabaseException('Database is disabled');
        }

        try {
            $condition = $this->prepareCondition($condition);

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
        if (!Config::get('DATABASE', false) || $this->useTable === false) {
            throw new DatabaseException('Database is disabled');
        }

        try {
            $condition = $this->prepareCondition($condition);

            return !empty($this->read($condition, [$this->useTable . '.id'], $this->useTable . '.id DESC'));
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
        if (!Config::get('DATABASE', false) || $this->useTable === false) {
            throw new DatabaseException('Database is disabled');
        }

        try {
            $middlewareData = ['model' => $this, 'query' => $sql, 'type' => 'create'];
            Kernel::$middlewareManager->runHookWithReference('processingModelQuery', $middlewareData);
            $sql = $middlewareData['query'];

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
     * Bind table
     * @param array|BindType $bind
     * @param string|null $tableName
     * @param ?string $primaryKey
     * @param ?string $foreignKey
     * @param array|Condition|null $condition
     * @param ?string $tableAlias
     * @return $this
     */
    public function bind(
        BindType|array $bind,
        ?string $tableName = null,
        ?string $primaryKey = null,
        ?string $foreignKey = null,
        null|array|Condition $condition = null,
        ?string $tableAlias = null
    ): self
    {
        $this->table->bind($bind, $tableName, $primaryKey, $foreignKey, $condition, $tableAlias);

        return $this;
    }

    /**
     * Set condition
     * @param Condition|string $key
     * @param string|array|null $value
     * @return self
     */
    public function setCondition(Condition|string $key, null|string|array $value = null): self
    {
        if ($key instanceof Condition) {
            foreach ($this->conditions as $conditionKey => $condition) {
                if ($condition instanceof Condition) {
                    if ($condition->getColumn(true) === $key->getColumn(true)) {
                        $this->conditions[$conditionKey] = $key;

                        return $this;
                    }
                }
            }

            $this->conditions[] = $key;

            return $this;
        }

        $this->conditions[$key] = $value;

        return $this;
    }

    /**
     * Clear conditions
     * @return $this
     */
    public function clearConditions(): self
    {
        $this->conditions = [];

        return $this;
    }

    /**
     * Unbind all joins
     * @return $this
     */
    public function unbindAll(): self
    {
        $this->getTableInstance()->unbindAll();

        return $this;
    }

    /**
     * Prepare conditions
     * @param array|null $conditions
     * @return array|null
     */
    protected function prepareCondition(?array $conditions): ?array
    {
        $returnCondition = $this->conditions;

        foreach ($conditions ?? [] as $conditionKey => $conditionValue) {
            if ($conditionValue instanceof Condition) {
                $add = false;

                foreach ($returnCondition as $conditionKey2 => $conditionValue2) {
                    if ($conditionValue2 instanceof Condition) {
                        if ($conditionValue2->getColumn(true) === $conditionValue->getColumn(true)) {
                            $returnCondition[$conditionKey2] = $conditionValue;
                            $add = true;

                            break;
                        }
                    }
                }

                if (!$add) {
                    $returnCondition[] = $conditionValue;
                }
            } else {
                $returnCondition[$conditionKey] = $conditionValue;
            }
        }

        return $returnCondition;
    }

}