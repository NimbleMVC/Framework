<?php

namespace NimblePHP\Framework;

use NimblePHP\Framework\Exception\NotFoundException;
use NimblePHP\Framework\Interfaces\ViewInterface;
use NimblePHP\Framework\Interfaces\ViewMiddlewareInterface;

/**
 * View
 */
class View implements ViewInterface
{

    /**
     * View path
     * @var string
     */
    protected string $viewPath = '/App/View/';

    /**
     * Response code
     * @var int
     */
    protected int $responseCode = 200;

    /**
     * View middleware
     * @var array
     */
    protected array $middleware = [];

    /**
     * Define variable
     */
    public function __construct()
    {
        $this->viewPath = Kernel::$projectPath . $this->viewPath;
    }

    /**
     * Set response code
     * @param int $responseCode
     * @return void
     */
    public function setResponseCode(int $responseCode): void
    {
        $this->responseCode = $responseCode;
    }

    /**
     * Render view
     * @param string $viewName
     * @param array $data
     * @return void
     * @throws NotFoundException
     */
    public function render(string $viewName, array $data = []): void
    {
        $this->runMiddleware('beforeRender', $viewName, $data);

        extract($data);
        $filePath = $this->viewPath . $viewName . '.phtml';

        if (!file_exists($filePath)) {
            throw new NotFoundException();
        }

        ob_start();
        include($filePath);
        $content = ob_get_clean();

        $this->runMiddleware('afterRender', $viewName, $data, $content);

        $response = new Response();
        $response->setContent($content);
        $response->setStatusCode($this->responseCode);
        $response->send();
    }

    /**
     * Run view middleware
     * @param string $method
     * @param mixed ...$args
     * @return void
     */
    protected function runMiddleware(string $method, ...$args): void
    {
        foreach ($this->middleware as $middlewareClass) {
            if (class_exists($middlewareClass)) {
                $middleware = new $middlewareClass();

                if ($middleware instanceof ViewMiddlewareInterface && method_exists($middleware, $method)) {
                    $middleware->$method(...$args);
                }
            }
        }
    }

}