<?php

namespace NimblePHP\framework;

use ErrorException;
use Exception;
use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use Krzysztofzylka\Env\Env;
use Krzysztofzylka\File\File;
use NimblePHP\framework\Abstracts\AbstractController;
use NimblePHP\framework\Exception\DatabaseException;
use NimblePHP\framework\Exception\HiddenException;
use NimblePHP\framework\Exception\NotFoundException;
use NimblePHP\framework\Interfaces\KernelInterface;
use NimblePHP\framework\Interfaces\MiddlewareInterface;
use NimblePHP\framework\Interfaces\RequestInterface;
use NimblePHP\framework\Interfaces\ResponseInterface;
use NimblePHP\framework\Interfaces\RouteInterface;
use NimblePHP\framework\Attributes\Http\Action;
use Throwable;

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
     * Middleware class
     * @var MiddlewareInterface
     */
    public static MiddlewareInterface $middleware;

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
     * @throws Exception
     */
    public function __construct(RouteInterface $router)
    {
        self::$projectPath = $this->getProjectPath();

        $this->router = $router;
        $this->request = new Request();
        $this->response = new Response();

        $this->loadConfiguration();
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
        $this->autoloader();

        if (isset(self::$middleware)) {
            self::$middleware->afterBootstrap();
        }

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
            Log::log($errstr, 'ERR', ['errno' => $errno, 'errfile' => $errfile, 'errline' => $errline]);

            throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
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

        if (class_exists('Middleware')) {
            self::$middleware = new \Middleware();
        } else {
            self::$middleware = new Middleware();
        }
    }

    /**
     * Load controller
     * @return void
     * @throws NotFoundException
     */
    protected function loadController(): void
    {
        $this->router->reload();
        $controllerName = $this->router->getController();
        $methodName = $this->router->getMethod();
        $params = $this->router->getParams();

        if (isset(self::$middleware)) {
            self::$middleware->beforeController($controllerName, $methodName, $params);
        }

        $controllerClass = '\App\Controller\\' . $controllerName;

        if (!class_exists($controllerClass)) {
            throw new NotFoundException('Controller ' . $controllerName . ' not found');
        }

        /** @var AbstractController $controller */
        $controller = new $controllerClass();

        if (!method_exists($controller, $methodName)) {
            throw new NotFoundException('Method ' . $methodName . ' does not exist');
        }

        $reflection = new \ReflectionMethod($controller, $methodName);
        $attributes = $reflection->getAttributes(Action::class);

        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();

            if (method_exists($instance, 'handle')) {
                $instance->handle($controller, $methodName, $params);
            }
        }

        $controller->name = str_replace('\App\Controller\\', '', $controllerName);
        $controller->action = $methodName;
        $controller->request = new Request();
        $controller->response = new Response();
        $controller->afterConstruct();

        call_user_func_array([$controller, $methodName], $params);

        if (isset(self::$middleware)) {
            self::$middleware->afterController($controllerName, $methodName, $params);
        }
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
        $data = ['exception' => $exception->getMessage(), 'file' => $exception->getFile()];

        if ($exception instanceof HiddenException) {
            $message = $exception->getHiddenMessage();
        }

        if ($exception->getPrevious()) {
            $data['previous_message'] = $exception->getPrevious()->getMessage();

            if (method_exists($exception->getPrevious(), 'getHiddenMessage')) {
                $data['previous_hidden_message'] = $exception->getPrevious()->getHiddenMessage();
            }
        }

        Log::log($message, 'ERR', $data);

        if (isset(self::$middleware)) {
            self::$middleware->handleException($exception);
        }

        throw $exception;
    }

    /**
     * Debug options
     * @return void
     */
    protected function debug(): void
    {
        if (!$_ENV['DEBUG']) {
            return;
        }

        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
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

}