<?php

namespace NimblePHP\Framework;

use ErrorException;
use Exception;
use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use Krzysztofzylka\Env\Env;
use Krzysztofzylka\File\File;
use NimblePHP\Framework\Abstracts\AbstractController;
use NimblePHP\Framework\Attributes\Http\Action;
use NimblePHP\Framework\Container\ServiceContainer;
use NimblePHP\Framework\Exception\DatabaseException;
use NimblePHP\Framework\Exception\HiddenException;
use NimblePHP\Framework\Exception\NotFoundException;
use NimblePHP\Framework\Interfaces\KernelInterface;
use NimblePHP\Framework\Interfaces\RequestInterface;
use NimblePHP\Framework\Interfaces\ResponseInterface;
use NimblePHP\Framework\Interfaces\RouteInterface;
use NimblePHP\Framework\Middleware\MiddlewareManager;
use ReflectionMethod;
use Throwable;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

/**
 * Kernel
 */
class Kernel implements KernelInterface
{

    /**
     * Project path
     * @var string
     */
    public static string $projectPath;

    /**
     * Middleware manager
     * @var MiddlewareManager
     */
    public static MiddlewareManager $middlewareManager;

    /**
     * Service container
     * @var ServiceContainer
     */
    public static ServiceContainer $serviceContainer;

    /**
     * Route class
     * @var RouteInterface
     */
    protected RouteInterface $router;

    /**
     * Request class
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * Response class
     * @var ResponseInterface
     */
    protected ResponseInterface $response;

    /**
     * Constructor
     * @param RouteInterface $router
     * @param RequestInterface|null $request
     * @param ResponseInterface|null $response
     * @throws Exception
     */
    public function __construct(RouteInterface $router, ?RequestInterface $request = null, ?ResponseInterface $response = null)
    {
        self::$projectPath = $this->getProjectPath();

        $this->router = $router;
        $this->request = $request ?? new Request();
        $this->response = $response ?? new Response();

        $this->loadConfiguration();
        $this->initializeDebugHandler();
        $this->autoloader();
        self::$middlewareManager = new MiddlewareManager();
        self::$serviceContainer = ServiceContainer::getInstance();

        $this->registerServices();
    }

    /**
     * Register services
     * @return void
     */
    protected function registerServices(): void
    {
        self::$serviceContainer->set('kernel.router', $this->router);
        self::$serviceContainer->set('kernel.request', $this->request);
        self::$serviceContainer->set('kernel.response', $this->response);
        self::$serviceContainer->set('kernel.session', new Session());
        self::$serviceContainer->set('kernel.cache', new Cache());
    }

    /**
     * Get project path
     * @return string
     */
    protected function getProjectPath(): string
    {
        return realpath(dirname($_SERVER['SCRIPT_FILENAME']) . '/../');
    }

    /**
     * Runner
     * @return void
     * @throws Throwable
     */
    public function handle(): void
    {
        try {
            $this->bootstrap();
            $this->loadController();
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * Load configuration
     * @return void
     * @throws Exception
     */
    public function loadConfiguration(): void
    {
        $env = new Env();
        $env->loadFromFile(__DIR__ . '/Default/.env');

        if (file_exists(self::$projectPath . '/.env')) {
            $env->loadFromFile(self::$projectPath . '/.env');
        }

        if (file_exists(self::$projectPath . '/.env.local')) {
            $env->loadFromFile(self::$projectPath . '/.env.local');
        }

        $env->loadFromSystem();
    }

    /**
     * Bootstrap
     * @return void
     * @throws DatabaseException
     * @throws Throwable
     */
    public function bootstrap(): void
    {
        $this->errorCatcher();
        $this->autoCreator();
        $this->initializeSession();
        $this->debug();
        $this->connectToDatabase();
        $this->router::registerRoutes(self::$projectPath . '/App/Controller', 'App\Controller');
        self::$middlewareManager->runHook('afterBootstrap');
        $this->loadModules();
    }

    /**
     * Error catcher
     * @return void
     * @throws ErrorException
     * @throws Exception
     */
    protected function errorCatcher(): void
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            if (!(error_reporting() & $errno)) {
                return false;
            }

            Log::log($errstr, 'ERR', ['errno' => $errno, 'errfile' => $errfile, 'errline' => $errline]);
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        });
    }

    /**
     * Auto directory creator
     * @return void
     * @throws Exception
     */
    protected function autoCreator(): void
    {
        File::mkdir([
            self::$projectPath . '/public',
            self::$projectPath . '/public/assets',
            self::$projectPath . '/App/Controller',
            self::$projectPath . '/App/View',
            self::$projectPath . '/App/Model',
            self::$projectPath . '/storage',
            self::$projectPath . '/storage/logs'
        ]);
    }

    /**
     * Initialize session
     * @return void
     */
    protected function initializeSession(): void
    {
        Session::init();
    }

    /**
     * Connect to database
     * @return void
     * @throws DatabaseException
     * @throws Throwable
     */
    protected function connectToDatabase(): void
    {
        try {
            if (!$_ENV['DATABASE']) {
                return;
            }

            if (DatabaseManager::$connection ?? false) {
                return;
            }

            $connect = DatabaseConnect::create();

            switch ($_ENV['DATABASE_TYPE']) {
                case 'mysql':
                    $connect->setType(DatabaseType::mysql);
                    $connect->setHost(trim($_ENV['DATABASE_HOST']));
                    $connect->setDatabaseName(trim($_ENV['DATABASE_NAME']));
                    $connect->setUsername(trim($_ENV['DATABASE_USERNAME']));
                    $connect->setPassword(trim($_ENV['DATABASE_PASSWORD']));
                    $connect->setPort((int)$_ENV['DATABASE_PORT']);
                    break;
                case 'sqlite':
                    $connect->setType(DatabaseType::sqlite);
                    $connect->setSqlitePath($this->getProjectPath() . DIRECTORY_SEPARATOR . $_ENV['DATABASE_PATH']);
                    break;
                default:
                    throw new DatabaseException('Invalid database type');
            }


            $connect->setCharset($_ENV['DATABASE_CHARSET']);

            $manager = new DatabaseManager();
            $manager->connect($connect);
        } catch (DatabaseManagerException $exception) {
            throw new DatabaseException($exception->getHiddenMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Class auto loader
     * @return void
     */
    protected function autoloader(): void
    {
        spl_autoload_register(function ($className) {
            $className = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $className);
            $file = self::$projectPath . '/' . $className . '.php';

            if (file_exists($file)) {
                require($file);
            }
        });
    }

    /**
     * Load controller
     */
    protected function loadController(): void
    {
        $this->router->reload();
        $controllerName = $this->router->getController();
        $methodName = $this->router->getMethod();
        $params = $this->router->getParams();

        $controllerMiddlewareContext = ['controllerName' => $controllerName, 'methodName' => $methodName, 'params' => $params];
        Kernel::$middlewareManager->runHookWithReference('beforeController', $controllerMiddlewareContext);
        $this->router->setController($controllerMiddlewareContext['controllerName']);
        $this->router->setMethod($controllerMiddlewareContext['methodName']);;
        $this->router->setParams($controllerMiddlewareContext['params']);
        $controllerName = $this->router->getController();
        $methodName = $this->router->getMethod();
        $params = $this->router->getParams();

        if (!$this->router->validate()) {
            throw new NotFoundException('Route /' . $controllerName . (!is_null($methodName) ? '/' . $methodName : '') . ' does not exist');
        }

        $controllerClass = str_replace('/', '\\', ('\App\Controller\\' . $controllerName));

        if (!class_exists($controllerClass)) {
            throw new NotFoundException('Controller ' . $controllerName . ' not found');
        }

        /** @var AbstractController $controller */
        $controller = new $controllerClass();

        if (!method_exists($controller, $methodName)) {
            throw new NotFoundException('Method ' . $methodName . ' does not exist');
        }

        $controller->name = str_replace('\App\Controller\\', '', $controllerName);
        $controller->action = $methodName;
        $reflection = new ReflectionMethod($controller, $methodName);
        $attributes = $reflection->getAttributes(Action::class);

        Kernel::$middlewareManager->runHook('afterAttributesController', [$reflection, $controller]);

        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();

            if (method_exists($instance, 'handle')) {
                $instance->handle($controller, $methodName, $params);
            }
        }

        $controller->request = new Request();
        $controller->afterConstruct();
        DependencyInjector::inject($controller);
        $controller->$methodName(...$params);

        Kernel::$middlewareManager->runHook('afterController', [$controllerName, $methodName, $params]);
    }

    /**
     * Handle exception
     * @param Throwable $exception
     * @return void
     * @throws Throwable
     */
    protected function handleException(Throwable $exception): void
    {
        $message = $exception->getMessage();
        $data = [
            'exception' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'backtrace' => $exception->getTraceAsString()
        ];

        if ($exception instanceof HiddenException) {
            $message = $exception->getHiddenMessage();
        }

        if ($exception->getPrevious()) {
            $previous = $exception->getPrevious();
            $data['previous_message'] = $previous->getMessage();
            $data['previous_backtrace'] = $previous->getTraceAsString();

            if (method_exists($previous, 'getHiddenMessage')) {
                /** @var HiddenException $previous */
                $data['previous_hidden_message'] = $previous->getHiddenMessage();
            }
        }

        Log::log($message, 'FATAL_ERR', $data);

        throw $exception;
    }

    /**
     * Debug options
     * @return void
     */
    protected function debug(): void
    {
        if (!$_ENV['DEBUG']) {
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
        } else {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        }
    }

    /**
     * Load modules
     * @return void
     */
    protected function loadModules(): void
    {
        $moduleRegister = new ModuleRegister();
        $moduleRegister->autoRegister();

        foreach (ModuleRegister::getAll() as $module) {
            if (array_key_exists('service_providers', $module['classes']) && empty($module['classes']['service_providers'])) {
                foreach ($module['classes']['service_providers'] as $serviceProvider) {
                    if (method_exists($serviceProvider, 'register')) {
                        $serviceProvider->register();
                    }
                }
            }
        }
    }

    /**
     * Initialize debug handler
     * @return void
     */
    private function initializeDebugHandler(): void
    {
        if (!$_ENV['DEBUG']) {
            return;
        }

        $handler = new PrettyPageHandler();
        $handler->setPageTitle('Nimble Exception');
        $handler->addDataTable('Kernel', [
            'projectPath' => self::$projectPath
        ]);

        $whoops = new Run;
        $whoops->allowQuit(false);
        $whoops->pushHandler($handler);
        $whoops->register();
    }

}