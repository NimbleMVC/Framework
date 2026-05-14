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

    class InvalidLoadTarget
    {
    }
}

namespace {

use App\Model\DemoModel;
use App\Model\DemoModelModel;
use App\Model\DemoOrmRecord;
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
        Kernel::$projectPath = getcwd();
        $this->setStaticProperty(DemoOrmRecord::class, 'table', $this->createMock(Table::class));
        $this->setStaticProperty(Log::class, 'session', null);
        $this->setStaticProperty(Log::class, 'storage', null);
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
