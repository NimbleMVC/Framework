<?php

namespace NimblePHP\Framework;

use Cron\CronExpression;
use Exception;
use krzysztofzylka\DatabaseManager\CreateTable;
use krzysztofzylka\DatabaseManager\DatabaseLock;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use krzysztofzylka\DatabaseManager\Table;
use NimblePHP\Framework\Abstracts\AbstractController;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Interfaces\ControllerInterface;
use NimblePHP\Framework\Libs\Classes;
use NimblePHP\Framework\Traits\LogTrait;
use ReflectionClass;
use ReflectionMethod;

class Cron
{

    use LogTrait;

    public const PRIORITY_MINIMUM = -255;

    public const PRIORITY_LOW = -100;

    public const PRIORITY_NORMAL = 0;

    public const PRIORITY_MEDIUM = 100;

    public const PRIORITY_HIGH = 255;

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

            $createTable = new CreateTable();
            $createTable->setName($this->table->getName());
            $createTable->addIdColumn();
            $createTable->addSimpleVarcharColumn('type', 48);
            $createTable->addSimpleVarcharColumn('name', 255);
            $createTable->addSimpleVarcharColumn('action', 255);
            $createTable->addSimpleTextColumn('parameters');
            $createTable->addSimpleIntColumn('priority');
            $createTable->addSimpleVarcharColumn('status', 64);
            $createTable->addDateModifyColumn();
            $createTable->execute();
        }
    }

    /**
     * Add job
     * @param string $type
     * @param string $name
     * @param string $action
     * @param array $parameters
     * @param int $priority
     * @return void
     * @throws DatabaseManagerException
     * @throws NimbleException
     * @throws Exception
     */
    public function addJob(string $type, string $name, string $action, array $parameters = [], int $priority = self::PRIORITY_NORMAL): void
    {
        if ($type !== 'model') {
            $this->log('Failed create job, type must be "model"', 'ERR', [
                'type' => $type,
                'name' => $name,
                'action' => $action,
                'parameters' => $parameters,
                'priority' => $priority
            ]);

            throw new NimbleException('Type must be "model"');
        }

        $this->table->setId(null)->insert([
            'type' => $type,
            'name' => $name,
            'action' => $action,
            'parameters' => json_encode($parameters),
            'priority' => $priority,
            'status' => 'new'
        ]);
    }

    /**
     * @param AbstractController|null $controller
     * @return bool
     * @throws DatabaseManagerException
     * @throws NimbleException
     */
    public function runJob(?ControllerInterface $controller = null): bool
    {
        $this->databaseLock->lock('cron_run_jobs');
        $job = null;

        try {
            $job = $this->getJob();

            if (empty($job)) {
                $this->databaseLock->unlock('cron_run_jobs');
                return false;
            }

            $this->updateStatus($job[$this->table->getName()]['id'], 'processing');
            $this->databaseLock->unlock('cron_run_jobs');

            switch ($job[$this->table->getName()]['type']) {
                case 'model':
                    $modelName = $job[$this->table->getName()]['name'];
                    $action = $job[$this->table->getName()]['action'];
                    $parameters = $job[$this->table->getName()]['parameters'];
                    $controller = $controller ?? $this->getController();
                    $controller->loadModel($modelName)->$action(...json_decode($parameters));
                    break;
            }

            $this->table->delete($job[$this->table->getName()]['id']);

            return true;
        } catch (Exception $exception) {
            if ($job !== null) {
                $this->updateStatus($job[$this->table->getName()]['id'], 'failed');
            }

            $this->databaseLock->unlock('cron_run_jobs');
            $this->log('Cron job failed', 'ERR', [
                'job' => $job,
                'exception' => [
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    'trace' => $exception->getTraceAsString()
                ]
            ]);

            throw $exception;
        }
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

        foreach (Classes::getAllClasses($modelPath, $namespace) as $model) {
            if (!class_exists($model)) {
                continue;
            }

            $reflection = new ReflectionClass($model);
            $modelName = str_replace($reflection->getNamespaceName() . '\\', '', $reflection->getName());

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
                            $cron->priority
                        );
                    }
                }
            }
        }

        $this->databaseLock->unlock('cron_init_tasks');
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
     * Get next job
     * @return array
     * @throws DatabaseManagerException
     */
    private function getJob(): array
    {
        return $this->table->find(
            [
                $this->table->getName() . '.status' => 'new'
            ],
            null,
            $this->table->getName() . '.priority DESC'
        );
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