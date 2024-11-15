<?php

namespace Nimblephp\framework;

use DebugBar\DataCollector\PDO\PDOCollector;
use ErrorException;
use Exception;
use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use Krzysztofzylka\Env\Env;
use Krzysztofzylka\File\File;
use Krzysztofzylka\Reflection\Reflection;
use Nimblephp\debugbar\Collectors\ModuleCollector;
use Nimblephp\debugbar\Debugbar;
use Nimblephp\framework\Abstracts\AbstractController;
use Nimblephp\framework\Exception\DatabaseException;
use Nimblephp\framework\Exception\HiddenException;
use Nimblephp\framework\Exception\NotFoundException;
use Nimblephp\framework\Interfaces\KernelInterface;
use Nimblephp\framework\Interfaces\MiddlewareInterface;
use Nimblephp\framework\Interfaces\RequestInterface;
use Nimblephp\framework\Interfaces\ResponseInterface;
use Nimblephp\framework\Interfaces\RouteInterface;
use ReflectionException;
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
     * Middleware class
     * @var MiddlewareInterface
     */
    protected MiddlewareInterface $middleware;

    /**
     * Active debugbar
     * @var bool
     */
    public static bool $activeDebugbar = false;

    /**
     * Constructor
     * @param RouteInterface $router
     */
    public function __construct(RouteInterface $router)
    {
        self::$projectPath = $this->getProjectPath();

        $this->router = $router;
        $this->request = new Request();
        $this->response = new Response();

        $this->loadConfiguration();

        self::$activeDebugbar = $_ENV['DEBUG'] && ModuleRegister::moduleExistsInVendor('nimblephp/debugbar');

        if (self::$activeDebugbar) {
            (new Debugbar())->init();
        }
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
        $env->loadFromSystem();
        $env->loadFromFile(__DIR__ . '/Default/.env');

        if (file_exists(self::$projectPath . '/.env')) {
            $env->loadFromFile(self::$projectPath . '/.env');
        }

        if (file_exists(self::$projectPath . '/.env.local')) {
            $env->loadFromFile(self::$projectPath . '/.env.local');
        }
    }

    /**
     * Bootstrap
     * @return void
     * @throws DatabaseException
     * @throws Throwable
     */
    public function bootstrap(): void
    {
        if (self::$activeDebugbar) {
            Debugbar::startTime('bootstrap', 'Bootstrap');
        }

        $this->errorCatcher();
        $this->autoCreator();
        $this->initializeSession();
        $this->debug();
        $this->connectToDatabase();
        $this->autoloader();

        if (isset($this->middleware)) {
            $this->middleware->afterBootstrap();
        }

        if (self::$activeDebugbar) {
            Debugbar::stopTime('bootstrap');
            Debugbar::startTime('load_modules', 'Load modules');
        }

        $this->loadModules();

        if (self::$activeDebugbar) {
            if (!Debugbar::$debugBar->hasCollector('module_register')) {
                Debugbar::$debugBar->addCollector(new ModuleCollector(ModuleRegister::getAll()));
            }

            Debugbar::stopTime('load_modules');
        }
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
            self::$projectPath . '/src/Controller',
            self::$projectPath . '/src/View',
            self::$projectPath . '/src/Model',
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
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
            if (!Config::get('DATABASE')) {
                return;
            }

            $connect = DatabaseConnect::create();

            switch (Config::get('DATABASE_TYPE')) {
                case 'mysql':
                    $connect->setType(DatabaseType::mysql);
                    $connect->setHost(trim(Config::get('DATABASE_HOST')));
                    $connect->setDatabaseName(trim(Config::get('DATABASE_NAME')));
                    $connect->setUsername(trim(Config::get('DATABASE_USERNAME')));
                    $connect->setPassword(trim(Config::get('DATABASE_PASSWORD')));
                    $connect->setPort((int)Config::get('DATABASE_PORT'));
                    break;
                case 'sqlite':
                    $connect->setType(DatabaseType::sqlite);
                    $connect->setSqlitePath($this->getProjectPath() . DIRECTORY_SEPARATOR . Config::get('DATABASE_PATH'));
                    break;
                default:
                    throw new DatabaseException('Invalid database type');
            }


            $connect->setCharset(Config::get('DATABASE_CHARSET'));

            $manager = new DatabaseManager();
            $manager->connect($connect);

            if (self::$activeDebugbar && !Debugbar::$debugBar->hasCollector('pdo')) {
                Debugbar::$debugBar->addCollector(new PDOCollector(DatabaseManager::$connection->getConnection()));
            }
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
            $this->middleware = new \Middleware();
        } else {
            $this->middleware = new Middleware();
        }
    }

    /**
     * Load controller
     * @return void
     * @throws NotFoundException
     * @throws ReflectionException
     */
    protected function loadController(): void
    {
        if (self::$activeDebugbar) {
            $debugbarUuid = Debugbar::uuid();
            Debugbar::startTime($debugbarUuid, 'Load main controller');
        }

        $this->router->reload();
        $controllerName = $this->router->getController();
        $methodName = $this->router->getMethod();
        $params = $this->router->getParams();

        if (isset($this->middleware)) {
            $this->middleware->beforeController($controllerName, $methodName, $params);
        }

        $controllerClass = '\src\Controller\\' . $controllerName;

        if (!class_exists($controllerClass)) {
            throw new NotFoundException('Controller ' . $controllerName . ' not found');
        }

        /** @var AbstractController $controller */
        $controller = new $controllerClass();

        if (!method_exists($controller, $methodName)) {
            throw new NotFoundException('Method ' . $methodName . ' does not exist');
        }

        $methodComments = Reflection::getClassMethodComment($controller, $methodName);

        if (Reflection::findClassComment($methodComments, 'action', 'disabled')) {
            throw new NotFoundException('Method ' . $methodName . ' is disabled');
        }

        $controller->name = str_replace('\src\Controller\\', '', $controllerName);
        $controller->action = $methodName;
        $controller->request = new Request();
        $controller->response = new Response();
        $controller->afterConstruct();

        call_user_func_array([$controller, $methodName], $params);

        if (isset($this->middleware)) {
            $this->middleware->afterController($controllerName, $methodName, $params);
        }

        if (self::$activeDebugbar) {
            Debugbar::stopTime($debugbarUuid);
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

        if (isset($this->middleware)) {
            $this->middleware->handleException($exception);
        }

        if (self::$activeDebugbar && $exception instanceof Exception) {
            Debugbar::addException($exception);
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