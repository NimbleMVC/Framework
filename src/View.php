<?php

namespace NimblePHP\Framework;

use NimblePHP\Framework\Exception\NotFoundException;
use NimblePHP\Framework\Interfaces\ViewInterface;

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
        extract($data);
        $filePath = $this->viewPath . $viewName . '.phtml';

        if (!file_exists($filePath)) {
            throw new NotFoundException();
        }

        ob_start();
        include($filePath);
        $content = ob_get_clean();

        $response = new Response();
        $response->setContent($content);
        $response->setStatusCode($this->responseCode);
        $response->send();
    }

}