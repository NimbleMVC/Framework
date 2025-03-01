<?php

namespace NimblePHP\framework\Abstracts;

use Exception;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use krzysztofzylka\DatabaseManager\Table;
use Nimblephp\framework\Attributes\Database\DataType;
use Nimblephp\framework\Attributes\Database\DefaultValue;
use ReflectionClass;
use ReflectionProperty;

abstract class AbstractORM
{

    /**
     * Table name
     * @var string
     */
    protected static string $tableName;

    /**
     * Table instance
     * @var Table
     */
    protected static Table $table;

    /**
     * Construct ORM object
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Get table name
     * @return string
     */
    public static function getTableName(): string
    {
        return static::$tableName ?? strtolower((new ReflectionClass(static::class))->getShortName());
    }

    /**
     * Get table instance
     * @return Table
     */
    public static function getTableInstance(): Table
    {
        if (!isset(static::$table)) {
            self::$table = new Table(static::getTableName());
        }

        return static::$table;
    }

    /**
     * Get columns
     * @return array
     */
    public static function getColumns(): array
    {
        $columns = [];
        $reflect = new ReflectionClass(static::class);

        foreach ($reflect->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $columnData = [
                'type' => $property->getType()?->getName() ?? 'string'
            ];

            foreach ($property->getAttributes(DataType::class) as $attribute) {
                $columnData['type'] = $attribute->newInstance()->type;
            }

            foreach ($property->getAttributes(DefaultValue::class) as $attribute) {
                $columnData['default'] = $attribute->newInstance()->value;
            }

            switch ($columnData['type']) {
                case 'bool':
                case 'boolean':
                    $columnData['type'] = 'tinyint(1)';
                    break;
                case 'array':
                    $columnData['type'] = 'json';
                    break;
                case 'string':
                    $columnData['type'] = 'varchar(255)';
                    break;
            }

            if ($property->getName() === 'id') {
                $columnData['auto_increment'] = true;
                $columnData['primary_key'] = true;
                $columnData['unsigned'] = true;
            }

            $columns[$property->getName()] = $columnData;
        }

        return $columns;
    }

    /**
     * Find element
     * @param array $conditions
     * @return static|null
     * @throws DatabaseManagerException
     */
    public static function read(array $conditions = []): ?static
    {
        $data = self::getTableInstance()->find($conditions);

        if (isset($data[self::getTableName()]) && is_array($data[self::getTableName()])) {
            $data = $data[self::getTableName()];
        }

        return $data ? new static($data) : null;
    }

    /**
     * Get all elements
     * @param array $conditions
     * @return array
     * @throws DatabaseManagerException
     */
    public static function readAll(array $conditions = []): array
    {
        $data = self::getTableInstance()->findAll($conditions);

        $models = [];

        foreach ($data as $row) {
            if (isset($row['account']) && is_array($row['account'])) {
                $models[] = new static($row['account']);
            } else {
                $models[] = new static($row);
            }
        }

        return $models;
    }

    /**
     * Insert or update data
     * @return bool
     * @throws DatabaseManagerException
     */
    public function save(): bool
    {
        $columns = array_keys(static::getColumns());

        foreach ($columns as $key => $column) {
            if (!isset($this->$column)) {
                unset($columns[$key]);
            }
        }

        $values = array_map(fn($col) => $this->$col, $columns);
        $data = array_combine($columns, $values);

        if (!empty($this->id)) {
            unset($data['id']);
            return self::getTableInstance()->setId($this->id)->update($data);
        } else {
            return self::getTableInstance()->setId(null)->insert($data);
        }
    }

    /**
     * Delete
     * @return bool
     * @throws DatabaseManagerException
     * @throws Exception
     */
    public function delete(): bool
    {
        if (empty($this->id)) {
            throw new Exception("ID is required for delete");
        }

        $delete = self::getTableInstance()->delete($this->id);
        self::getTableInstance()->setId(null);

        return $delete;
    }

    /**
     * Get array
     * @return array
     */
    public function toArray(): array
    {
        $data = [];

        foreach (static::getColumns() as $column => $type) {
            $data[$column] = $this->$column ?? null;
        }

        return [self::getTableName() => $data];
    }

}