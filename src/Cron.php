<?php

namespace NimblePHP\Framework;

use Cron\CronExpression;
use Exception;
use krzysztofzylka\DatabaseManager\AlterTable;
use krzysztofzylka\DatabaseManager\Columns\DateCreatedColumn;
use krzysztofzylka\DatabaseManager\Columns\DateModifyColumn;
use krzysztofzylka\DatabaseManager\Columns\DatetimeColumn;
use krzysztofzylka\DatabaseManager\Columns\IdColumn;
use krzysztofzylka\DatabaseManager\Columns\IntColumn;
use krzysztofzylka\DatabaseManager\Columns\TextColumn;
use krzysztofzylka\DatabaseManager\Columns\VarcharColumn;
use krzysztofzylka\DatabaseManager\CreateTable;
use krzysztofzylka\DatabaseManager\DatabaseLock;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use krzysztofzylka\DatabaseManager\Table;
use NimblePHP\Framework\Abstracts\AbstractController;
use NimblePHP\Framework\Enums\ModelTypeEnum;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Interfaces\ControllerInterface;
use NimblePHP\Framework\Libs\Classes;
use NimblePHP\Framework\Module\ModuleRegister;
use NimblePHP\Framework\Traits\LogTrait;
use ReflectionClass;
use ReflectionMethod;

class Cron
{

    use LogTrait;

    public const int PRIORITY_MINIMUM = -255;

    public const int PRIORITY_LOW = -100;

    public const int PRIORITY_NORMAL = 0;

    public const int PRIORITY_MEDIUM = 100;

    public const int PRIORITY_HIGH = 255;

    /**
     * Table instance
     * @var Table
     */
    public Table $table;

    /**
     * Database lock
     * @var DatabaseLock
     */
    public DatabaseLock $databaseLock;

    /**
     * Construct
     * @throws DatabaseManagerException
     */
    public function __construct()
    {
        $this->table = new Table('cron_job');
        $this->databaseLock = new DatabaseLock();

        if (!$this->table->exists()) {
            $this->log('create cron_job table');

            $createTable = new CreateTable($this->table->getName());
            $createTable->addColumn(new IdColumn());
            $createTable->addColumn(new VarcharColumn('type', size: 64));
            $createTable->addColumn(new VarcharColumn('name', size: 255));
            $createTable->addColumn(new VarcharColumn('action', size: 255));
            $createTable->addColumn(new TextColumn('parameters'));
            $createTable->addColumn((new IntColumn('priority'))->setDefault(0));
            $createTable->addColumn(new VarcharColumn('status', size: 20));
            $createTable->addColumn((new DatetimeColumn('date_run_after'))->setDefault(null));
            $createTable->addColumn((new DatetimeColumn('date_expiration'))->setDefault(null));
            $createTable->addColumn(new DateCreatedColumn());
            $createTable->addColumn(new DateModifyColumn());
            $createTable->execute();
        } else {
            if (!$this->table->columnList('date_created')) {
                $alterTable = new AlterTable($this->table->getName());
                $alterTable->addColumn((new DatetimeColumn('date_run_after'))->setDefault(null), 'status');
                $alterTable->addColumn((new DatetimeColumn('date_expiration'))->setDefault(null), 'date_run_after');
                $alterTable->addColumn(new DateCreatedColumn(), 'date_expiration');
                $alterTable->execute();
            }
        }

        $this->ensureIndexExists();
    }

    private function ensureIndexExists(): void
    {
        try {
            $pdo = $this->table->getPdoInstance();
            $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
            $tableName = $this->table->getName();
            $indexName = 'idx_cron_pick';

            if ($driver === 'mysql') {
                $stmt = $pdo->query("SHOW INDEX FROM `{$tableName}` WHERE Key_name = '{$indexName}'");
                if ($stmt === false) {
                    return;
                }

                $result = $stmt->fetchAll();
                if (!empty($result)) {
                    return;
                }

                $pdo->exec(
                    "ALTER TABLE `{$tableName}` ADD INDEX `{$indexName}` (status, priority, date_run_after)"
                );
                $this->log("Created index {$indexName} on {$tableName}");
            } elseif ($driver === 'pgsql') {
                $stmt = $pdo->prepare(
                    "SELECT 1 FROM pg_indexes WHERE tablename = ? AND indexname = ?"
                );
                $stmt->execute([$tableName, $indexName]);
                $result = $stmt->fetchAll();

                if (!empty($result)) {
                    return;
                }

                $pdo->exec(
                    "CREATE INDEX \"{$indexName}\" ON \"{$tableName}\" (status, priority, date_run_after)"
                );
                $this->log("Created index {$indexName} on {$tableName}");
            }
        } catch (\Throwable $exception) {
            $this->log('Failed to ensure index exists', 'WARN', ['error' => $exception->getMessage()]);
        }
    }

    /**
     * Add a job
     * @param string $type
     * @param string $name
     * @param string $action
     * @param array $parameters
     * @param int $priority
     * @param string|null $runAfterDate
     * @param string|null $expirationDate
     * @return void
     * @throws DatabaseManagerException
     * @throws NimbleException
     * @throws Exception
     */
    public function addJob(string $type, string $name, string $action, array $parameters = [], int $priority = self::PRIORITY_NORMAL, ?string $runAfterDate = null, ?string $expirationDate = null): void
    {
        if ($type !== 'model') {
            $this->log('Failed create job, type must be "model"', 'ERR', [
                'type' => $type,
                'name' => $name,
                'action' => $action,
                'parameters' => $parameters,
                'priority' => $priority,
                'runAfterDate' => $runAfterDate,
                'expirationDate' => $expirationDate
            ]);

            throw new NimbleException('Type must be "model"');
        }

        if ($runAfterDate === null) {
            $runAfterDate = date('Y-m-d H:i:s');
        }

        $this->table->setId()->insert([
            'type' => $type,
            'name' => $name,
            'action' => $action,
            'parameters' => json_encode($parameters),
            'priority' => $priority,
            'status' => 'new',
            'date_run_after' => $runAfterDate,
            'date_expiration' => $expirationDate
        ]);
    }

    /**
     * Run a job
     * @param AbstractController|null $controller
     * @param callable|null $output
     * @return bool
     * @throws DatabaseManagerException
     * @throws NimbleException
     */
    public function runJob(?ControllerInterface $controller = null, ?callable $output = null): bool
    {
        $jobData = $this->reserveJob();

        if ($jobData === null) {
            return false;
        }

        $jobId = (int)$jobData['id'];
        $jobExpirationDate = $jobData['date_expiration'];

        if (!empty($jobExpirationDate) && strtotime($jobExpirationDate) <= time()) {
            $this->table->delete($jobId);

            return true;
        }

        try {
            switch ($jobData['type']) {
                case 'model':
                    $modelName = $jobData['name'];
                    $action = $jobData['action'];
                    $parameters = $jobData['parameters'];
                    $this->emitOutput(
                        $output,
                        'Run job model ' . $modelName . ', action ' . $action . ', parameters ' . $parameters
                    );
                    $controller = $controller ?? $this->getController();
                    $controller->loadModel($modelName)->$action(...json_decode($parameters));
                    break;
            }

            $this->table->delete($jobId);

            return true;
        } catch (\Throwable $exception) {
            $this->updateStatus($jobId, 'failed');
            $this->logException('Cron job failed', $exception, ['job' => $jobData]);
        }

        return false;
    }

    /**
     * Atomically fetch and claim the next runnable job.
     *
     * The job is selected and flipped from "new" to "processing" inside a single
     * transaction. On MySQL/PostgreSQL the row is picked with FOR UPDATE SKIP LOCKED,
     * so every worker grabs a different queued row and never waits on a row another
     * worker already holds. This removes both the "thundering herd" on the highest
     * priority row and the deadlocks that arise when many workers contend for the same
     * rows, while still guaranteeing a job is claimed by at most one worker across all
     * processes and pods sharing the database.
     *
     * SKIP LOCKED is unavailable on SQLite; there the whole-database write lock already
     * serialises writers, and the conditional UPDATE below keeps the claim exclusive.
     * @return array<string, mixed>|null Claimed job row, or null when nothing is runnable
     * @throws DatabaseManagerException
     */
    private function reserveJob(): ?array
    {
        $tableName = $this->table->getName();
        $pdo = $this->table->getPdoInstance();
        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $lockClause = in_array($driver, ['mysql', 'pgsql'], true) ? ' FOR UPDATE SKIP LOCKED' : '';
        $now = date('Y-m-d H:i:s');

        $pdo->beginTransaction();

        try {
            $select = $pdo->prepare(
                'SELECT * FROM ' . $tableName
                . " WHERE status = 'new' AND (date_run_after <= :now OR date_run_after IS NULL)"
                . ' ORDER BY priority DESC, id ASC'
                . ' LIMIT 1' . $lockClause
            );
            $select->bindValue(':now', $now);
            $select->execute();
            $row = $select->fetch(\PDO::FETCH_ASSOC);

            if ($row === false) {
                $pdo->commit();

                return null;
            }

            $update = $pdo->prepare(
                'UPDATE ' . $tableName . " SET status = 'processing' WHERE id = :id AND status = 'new'"
            );
            $update->bindValue(':id', (int)$row['id'], \PDO::PARAM_INT);
            $update->execute();
            $claimed = $update->rowCount() === 1;

            $pdo->commit();

            return $claimed ? $row : null;
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw new DatabaseManagerException($exception->getMessage());
        }
    }

    /**
     * Log exception
     * @param string $message
     * @param \Throwable $exception
     * @param array $context
     * @return void
     */
    private function logException(string $message, \Throwable $exception, array $context = []): void
    {
        $exceptionMessage = $exception instanceof DatabaseManagerException
            ? $exception->getHiddenMessage()
            : $exception->getMessage();

        $this->log($message, 'ERR', $context + [
                'exception' => [
                    'message' => $exceptionMessage,
                    'code' => $exception->getCode(),
                    'trace' => $exception->getTraceAsString()
                ]
            ]);
    }

    /**
     * @param callable|null $output
     * @param string $message
     * @return void
     */
    private function emitOutput(?callable $output, string $message): void
    {
        if ($output === null) {
            return;
        }

        $output($message);
    }

    /**
     * Init tasks
     * @param string $modelPath
     * @param string $namespace
     * @return void
     * @throws DatabaseManagerException
     * @throws NimbleException
     */
    public function initTasks(string $modelPath, string $namespace): void
    {
        $this->databaseLock->lock('cron_init_tasks');
        $cronCache = [];

        $this->scanTasksFromDirectory($modelPath, $namespace, $cronCache);

        foreach ($this->resolveModuleTaskSources() as $moduleSource) {
            $this->scanTasksFromDirectory($moduleSource['path'], $moduleSource['namespace'], $cronCache, true);
        }

        $this->databaseLock->unlock('cron_init_tasks');
    }

    /**
     * Scan a directory for model methods annotated with the Cron attribute and enqueue the due ones
     * @param string $modelPath
     * @param string $namespace
     * @param array<string, CronExpression> $cronCache
     * @param bool $requireModelSuffix Only consider classes whose name ends with "Model". Used when scanning
     *     module source directories, which also hold non-model files (helpers, function definitions) whose
     *     autoloading would have unwanted side effects.
     * @return void
     * @throws DatabaseManagerException
     * @throws NimbleException
     */
    private function scanTasksFromDirectory(string $modelPath, string $namespace, array &$cronCache, bool $requireModelSuffix = false): void
    {
        foreach (Classes::getAllClasses($modelPath, $namespace) as $model) {
            if ($requireModelSuffix && !str_ends_with($model, 'Model')) {
                continue;
            }

            if (!class_exists($model)) {
                continue;
            }

            $reflection = new ReflectionClass($model);
            $modelType = ModelTypeEnum::V1;

            if (str_ends_with($reflection->getName(), 'Model')) {
                $modelType = ModelTypeEnum::V2;
            }

            if ($modelType === ModelTypeEnum::V2) {
                $modelName = $reflection->getName();
            } else {
                $modelName = str_replace($reflection->getNamespaceName() . '\\', '', $reflection->getName());
            }

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $attributes = $method->getAttributes(Attributes\Cron\Cron::class);

                if (empty($attributes)) {
                    continue;
                }

                foreach ($attributes as $attribute) {
                    /** @var Attributes\Cron\Cron $cron */
                    $cron = $attribute->newInstance();
                    $timeKey = $cron->time;

                    if (!isset($cronCache[$timeKey])) {
                        $cronCache[$timeKey] = new CronExpression($timeKey);
                    }

                    if ($cronCache[$timeKey]->isDue()) {
                        $this->addJob(
                            'model',
                            $modelName,
                            $method->getName(),
                            $cron->parameters,
                            $cron->priority,
                            $cron->runAfterDate,
                            $cron->expirationDate
                        );
                    }
                }
            }
        }
    }

    /**
     * Resolve cron task source directories provided by registered modules
     * @return array<int, array{path: string, namespace: string}>
     */
    private function resolveModuleTaskSources(): array
    {
        $sources = [];

        foreach (ModuleRegister::getAll() as $module) {
            $namespace = $module['namespace'] ?? null;
            $config = $module['config'] ?? null;

            if ($namespace === null || $config === null) {
                continue;
            }

            $path = $config->get('path');

            if (!is_string($path) || $path === '') {
                continue;
            }

            $modelPath = $path . DIRECTORY_SEPARATOR . 'src';

            if (!is_dir($modelPath)) {
                continue;
            }

            $sources[] = [
                'path' => $modelPath,
                'namespace' => '\\' . trim($namespace, '\\'),
            ];
        }

        return $sources;
    }

    /**
     * Get controller
     * @return ControllerInterface
     */
    private function getController(): ControllerInterface
    {
        $controller = new class extends AbstractController {
        };

        $controller->name = 'cronjob';
        $controller->action = 'cronjob';

        return $controller;
    }

    /**
     * Update status
     * @param int $id
     * @param string $status
     * @return void
     * @throws DatabaseManagerException
     * @throws NimbleException
     */
    private function updateStatus(int $id, string $status): void
    {
        if (!in_array($status, ['new', 'processing', 'runned', 'failed'])) {
            throw new NimbleException('Status must be "new", "processing", "runned" or "failed"');
        }

        $this->table->setId($id)->update([
            'status' => $status
        ]);
    }

}
