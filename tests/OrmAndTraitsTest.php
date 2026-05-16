<?php

namespace App\Model {

    use NimblePHP\Framework\Abstracts\AbstractModel;
    use NimblePHP\Framework\Attributes\Database\DataType;
    use NimblePHP\Framework\Attributes\Database\DefaultValue;

    class DemoOrmRecord extends \NimblePHP\Framework\Abstracts\AbstractORM
    {
        public int $id;

        public string $name;

        #[DataType('boolean')]
        #[DefaultValue('1')]
        public bool $enabled;

        #[DataType('array')]
        public array $meta;
    }

    class DemoModel extends AbstractModel
    {
    }

    class DemoModelModel extends AbstractModel
    {
    }

    class EventEnabledModel extends AbstractModel
    {
        public function setTableMock(\krzysztofzylka\DatabaseManager\Table $table): void
        {
            $this->table = $table;
        }
    }

    class InvalidLoadTarget
    {
    }
}

namespace {

use App\Model\DemoModel;
use App\Model\DemoModelModel;
use App\Model\DemoOrmRecord;
use App\Model\EventEnabledModel;
use NimblePHP\Framework\Event\Framework\AfterConstructModelEvent;
use NimblePHP\Framework\Event\Framework\AfterConstructOrmModelEvent;
use NimblePHP\Framework\Event\Framework\AfterModelCreateEvent;
use NimblePHP\Framework\Event\Framework\AfterModelDeleteEvent;
use NimblePHP\Framework\Event\Framework\AfterModelUpdateEvent;
use NimblePHP\Framework\Event\Framework\ProcessingModelDataEvent;
use NimblePHP\Framework\Event\Framework\ProcessingModelQueryEvent;
use NimblePHP\Framework\Abstracts\AbstractController;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Log;
use NimblePHP\Framework\Middleware\MiddlewareManager;
use NimblePHP\Framework\Request;
use NimblePHP\Framework\Traits\LoadModelTrait;
use NimblePHP\Framework\Traits\LogTrait;
use PHPUnit\Framework\TestCase;
use krzysztofzylka\DatabaseManager\Table;

class OrmAndTraitsTest extends TestCase
{
    protected function setUp(): void
    {
        $_ENV['DATABASE'] = false;
        $_ENV['LOG'] = true;
        Kernel::$middlewareManager = new MiddlewareManager();
        Kernel::$eventDispatcher = null;
        Kernel::$projectPath = getcwd();
        $this->setStaticProperty(DemoOrmRecord::class, 'table', $this->createMock(Table::class));
        $this->setStaticProperty(Log::class, 'session', null);
        $this->setStaticProperty(Log::class, 'storage', null);
    }

    protected function tearDown(): void
    {
        Kernel::$eventDispatcher = null;
    }

    public function testAbstractOrmColumnsRespectAttributesAndDefaults(): void
    {
        $columns = DemoOrmRecord::getColumns();

        $this->assertSame([
            'type' => 'int',
            'auto_increment' => true,
            'primary_key' => true,
            'unsigned' => true,
        ], $columns['id']);
        $this->assertSame(['type' => 'varchar(255)'], $columns['name']);
        $this->assertSame(['type' => 'tinyint(1)', 'default' => '1'], $columns['enabled']);
        $this->assertSame(['type' => 'json'], $columns['meta']);
    }

    public function testAbstractOrmReadReadAllSaveDeleteAndToArrayUseTableContract(): void
    {
        $ormEvents = [];
        Kernel::getEventDispatcher()->addListener(AfterConstructOrmModelEvent::class, function (AfterConstructOrmModelEvent $event) use (&$ormEvents): void {
            $ormEvents[] = $event->model::class;
        });
        $table = $this->createMock(Table::class);
        $table->expects($this->once())
            ->method('find')
            ->with(['id' => 5])
            ->willReturn(['demoormrecord' => ['id' => 5, 'name' => 'Alice', 'enabled' => true, 'meta' => ['x' => 1]]]);
        $table->expects($this->once())
            ->method('findAll')
            ->with(['enabled' => true])
            ->willReturn([
                ['account' => ['id' => 1, 'name' => 'A', 'enabled' => true, 'meta' => []]],
                ['id' => 2, 'name' => 'B', 'enabled' => false, 'meta' => ['b' => 1]],
            ]);
        $table->expects($this->exactly(3))
            ->method('setId')
            ->willReturnSelf();
        $table->expects($this->once())
            ->method('insert')
            ->with([
                'name' => 'Created',
                'enabled' => true,
                'meta' => ['new' => 1],
            ])
            ->willReturn(true);
        $table->expects($this->once())
            ->method('update')
            ->with([
                'name' => 'Updated',
                'enabled' => false,
                'meta' => ['upd' => 1],
            ])
            ->willReturn(true);
        $table->expects($this->once())
            ->method('delete')
            ->with(9)
            ->willReturn(true);

        $this->setStaticProperty(DemoOrmRecord::class, 'table', $table);

        $record = DemoOrmRecord::read(['id' => 5]);
        $all = DemoOrmRecord::readAll(['enabled' => true]);

        $this->assertInstanceOf(DemoOrmRecord::class, $record);
        $this->assertSame('Alice', $record->name);
        $this->assertCount(2, $all);
        $this->assertContains(DemoOrmRecord::class, $ormEvents);
        $this->assertSame('A', $all[0]->name);
        $this->assertSame('B', $all[1]->name);

        $newRecord = new DemoOrmRecord();
        $newRecord->name = 'Created';
        $newRecord->enabled = true;
        $newRecord->meta = ['new' => 1];
        $this->assertTrue($newRecord->save());

        $existing = new DemoOrmRecord(['id' => 9, 'name' => 'Updated', 'enabled' => false, 'meta' => ['upd' => 1]]);
        $this->assertTrue($existing->save());
        $this->assertTrue($existing->delete());
        $this->assertSame([
            'demoormrecord' => [
                'id' => 9,
                'name' => 'Updated',
                'enabled' => false,
                'meta' => ['upd' => 1],
            ],
        ], $existing->toArray());
    }

    public function testAbstractOrmDeleteRequiresId(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('ID is required for delete');

        (new DemoOrmRecord())->delete();
    }

    public function testLoadModelUsesControllerInstanceForClassicModel(): void
    {
        $modelEvents = [];
        Kernel::getEventDispatcher()->addListener(AfterConstructModelEvent::class, function (AfterConstructModelEvent $event) use (&$modelEvents): void {
            $modelEvents[] = $event->model::class;
        });
        $loader = new class extends AbstractController {
            public function index(): void
            {
            }
        };
        $loader->name = 'demo';
        $loader->action = 'index';
        $loader->request = new Request();

        $model = $loader->loadModel(DemoModel::class);

        $this->assertInstanceOf(DemoModel::class, $model);
        $this->assertSame($loader, $model->controller);
        $this->assertSame('Demo', $model->name);
        $this->assertContains(DemoModel::class, $modelEvents);
    }

    public function testLoadModelUsesV2NamingAndFallbackController(): void
    {
        $loader = new class {
            use LoadModelTrait;
        };

        $model = $loader->loadModel(DemoModelModel::class);

        $this->assertInstanceOf(DemoModelModel::class, $model);
        $this->assertSame(\NimblePHP\Framework\Enums\ModelTypeEnum::V2, $model->modelType);
        $this->assertSame('DemoModel', $model->name);
        $this->assertInstanceOf(AbstractController::class, $model->controller);
        $this->assertInstanceOf(Request::class, $model->controller->request);
    }

    public function testLoadModelUsesExistingControllerPropertyWhenAvailable(): void
    {
        $controller = new class extends AbstractController {
            public function index(): void
            {
            }
        };
        $controller->name = 'holder';
        $controller->action = 'index';
        $controller->request = new Request();

        $loader = new class($controller) {
            use LoadModelTrait;

            public function __construct(public AbstractController $controller)
            {
            }
        };

        $model = $loader->loadModel(DemoModel::class);

        $this->assertSame($controller, $model->controller);
    }

    public function testModelEventsCanMutateDataAndQueryBeforeTableExecution(): void
    {
        $_ENV['DATABASE'] = true;
        $capturedEvents = [];
        $afterLifecycleEvents = [];

        Kernel::getEventDispatcher()->addListener(ProcessingModelDataEvent::class, function (ProcessingModelDataEvent $event) use (&$capturedEvents): void {
            $capturedEvents[] = ['data', $event->type];
            $event->data['from_event'] = 'yes';
            if ($event->type === 'updateValue') {
                $event->data['title'] = strtoupper($event->data['title']);
            }
        }, 100);
        Kernel::getEventDispatcher()->addListener(ProcessingModelQueryEvent::class, function (ProcessingModelQueryEvent $event) use (&$capturedEvents): void {
            $capturedEvents[] = ['query', $event->type];
            $event->query = 'SELECT 2';
        }, 100);
        Kernel::getEventDispatcher()->addListener(AfterModelCreateEvent::class, function (AfterModelCreateEvent $event) use (&$afterLifecycleEvents): void {
            $afterLifecycleEvents[] = ['create', $event->data, $event->result];
        });
        Kernel::getEventDispatcher()->addListener(AfterModelUpdateEvent::class, function (AfterModelUpdateEvent $event) use (&$afterLifecycleEvents): void {
            $afterLifecycleEvents[] = [$event->type, $event->data, $event->result];
        });
        Kernel::getEventDispatcher()->addListener(AfterModelDeleteEvent::class, function (AfterModelDeleteEvent $event) use (&$afterLifecycleEvents): void {
            $afterLifecycleEvents[] = [$event->type, $event->id, $event->conditions, $event->result];
        });

        $table = $this->createMock(Table::class);
        $table->expects($this->once())
            ->method('setId')
            ->willReturnSelf();
        $table->expects($this->once())
            ->method('insert')
            ->with([
                'title' => 'draft',
                'from_event' => 'yes',
            ])
            ->willReturn(true);
        $table->expects($this->once())
            ->method('getId')
            ->willReturn(11);
        $table->expects($this->once())
            ->method('updateValue')
            ->with('title', 'DRAFT')
            ->willReturn(true);
        $table->expects($this->once())
            ->method('query')
            ->with('SELECT 2')
            ->willReturn([['ok' => true]]);
        $table->expects($this->once())
            ->method('delete')
            ->with(11)
            ->willReturn(true);
        $table->expects($this->once())
            ->method('deleteByConditions')
            ->with(['status' => 'archived'])
            ->willReturn(true);

        $model = new EventEnabledModel();
        $model->useTable = 'event_enabled';
        $model->setTableMock($table);

        $this->assertTrue($model->create(['title' => 'draft']));
        $this->assertSame(11, $model->getId());
        $this->assertTrue($model->updateValue('title', 'draft'));
        $this->assertSame([['ok' => true]], $model->query('SELECT 1'));
        $this->assertTrue($model->delete());
        $this->assertTrue($model->deleteByConditions(['status' => 'archived']));
        $this->assertSame([
            ['data', 'create'],
            ['data', 'updateValue'],
            ['query', 'create'],
        ], $capturedEvents);
        $this->assertSame([
            ['create', ['title' => 'draft', 'from_event' => 'yes'], true],
            ['updateValue', ['title' => 'DRAFT', 'from_event' => 'yes'], true],
            ['delete', 11, null, true],
            ['deleteByConditions', null, ['status' => 'archived'], true],
        ], $afterLifecycleEvents);
    }

    public function testLoadModelThrowsForMissingAndInvalidTargets(): void
    {
        $loader = new class {
            use LoadModelTrait;
        };

        try {
            $loader->loadModel('App\\Model\\MissingModel');
            $this->fail('Missing model should throw');
        } catch (\NimblePHP\Framework\Exception\NotFoundException $exception) {
            $this->assertSame('Not found model App\Model\MissingModel', $exception->getMessage());
        }

        $this->expectException(\NimblePHP\Framework\Exception\NimbleException::class);
        $this->expectExceptionMessage('Failed load model');
        $loader->loadModel(\App\Model\InvalidLoadTarget::class);
    }

    public function testLogTraitDelegatesToFrameworkLog(): void
    {
        $_GET = ['source' => 'trait'];
        $projectPath = sys_get_temp_dir() . '/nimble_log_trait_' . uniqid('', true);
        mkdir($projectPath . '/storage/logs', 0777, true);
        Kernel::$projectPath = $projectPath;
        $this->setStaticProperty(Log::class, 'session', 'TEST-SESSION');
        $this->setStaticProperty(Log::class, 'storage', new \NimblePHP\Framework\Storage('logs'));

        $logger = new class {
            use LogTrait;
        };

        $this->assertTrue($logger->log('Trait message', 'err', ['ok' => true]));

        $files = glob($projectPath . '/storage/logs/*.log.json');
        $this->assertCount(1, $files);
        $payload = json_decode(trim((string) file_get_contents($files[0])), true);
        $this->assertSame('ERROR', $payload['level']);
        $this->assertSame('Trait message', $payload['message']);

        $this->removeDirectory($projectPath);
    }

    private function setStaticProperty(string $class, string $property, mixed $value): void
    {
        $reflection = new \ReflectionProperty($class, $property);
        $reflection->setAccessible(true);

        if ($value === null && $reflection->getType()?->allowsNull() !== true) {
            return;
        }

        $reflection->setValue(null, $value);
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}

}
