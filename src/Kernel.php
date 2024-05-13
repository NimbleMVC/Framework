<?php

namespace Nimblephp\framework;

use Exception;
use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\DatabaseManager;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use Krzysztofzylka\File\File;
use Nimblephp\framework\Exception\DatabaseException;
use Nimblephp\framework\Exception\HiddenException;
use Nimblephp\framework\Exception\NotFoundException;
use Nimblephp\framework\Interfaces\KernelInterface;
use Nimblephp\framework\Interfaces\MiddlewareInterface;
use Nimblephp\framework\Interfaces\RequestInterface;
use Nimblephp\framework\Interfaces\ResponseInterface;
use Nimblephp\framework\Interfaces\RouteInterface;
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
     * Constructor
     * @param RouteInterface $router
     */
    public function __construct(RouteInterface $router)
    {
        self::$projectPath = $this->getProjectPath();

        $this->router = $router;
        $this->request = new Request();
        $this->response = new Response();
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
        Config::loadFromEnv(__DIR__ . '/Default/.env');

        if (file_exists(self::$projectPath . '/.env')) {
            Config::loadFromEnv(self::$projectPath . '/.env');
        }

        if (file_exists(self::$projectPath . '/local.env')) {
            Config::loadFromEnv(self::$projectPath . '/local.env');
        }
    }

    /**
     * Bootstrap
     * @return void
     * @throws DatabaseException
     * @throws Throwable
     */
    protected function bootstrap(): void
    {
        $this->autoCreator();
        $this->loadConfiguration();
        $this->initializeSession();
        $this->debug();
        $this->connectToDatabase();
        $this->autoloader();

        if (isset($this->middleware)) {
            $this->middleware->afterBootstrap();
        }
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
        session_start();
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
                    $connect->setHost(Config::get('DATABASE_HOST'));
                    $connect->setDatabaseName(Config::get('DATABASE_NAME'));
                    $connect->setUsername(Config::get('DATABASE_USERNAME'));
                    $connect->setPassword(Config::get('DATABASE_PASSWORD'));
                    $connect->setPort(Config::get('DATABASE_PORT'));
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

        if (class_exists('Middleware', false)) {
            $this->middleware = new \Middleware();
        } else {
            $this->middleware = new Middleware();
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

        if (isset($this->middleware)) {
            $this->middleware->beforeController($controllerName, $methodName, $params);
        }

        if (!class_exists($controllerName)) {
            throw new NotFoundException('Controller ' . $controllerName . ' not found');
        }

        /** @var Controller $controller */
        $controller = new $controllerName();

        if (!method_exists($controller, $methodName)) {
            throw new NotFoundException('Method ' . $methodName . ' does not exist');
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

        throw $exception;
    }

    /**
     * Debug options
     * @return void
     */
    protected function debug(): void
    {
        if (!Config::get('DEBUG')) {
            return;
        }

        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
    }

}