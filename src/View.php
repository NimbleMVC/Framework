<?php

namespace NimblePHP\Framework;

use NimblePHP\Framework\Event\Framework\AfterViewRenderEvent;
use NimblePHP\Framework\Event\Framework\BeforeViewRenderEvent;
use NimblePHP\Framework\Event\Framework\ProcessingViewDataEvent;
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
     * View set path
     * @param string $path
     * @return View
     */
    public function setViewPath(string $path): self
    {
        $this->viewPath = $path;

        return $this;
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
        $processingEvent = Kernel::dispatchEvent(new ProcessingViewDataEvent($data));
        $data = $processingEvent->data;
        Kernel::$middlewareManager->runHookWithReference('processingViewData', $data);
        $_previewData = $data;
        extract($data);
        $filePath = $this->viewPath . $viewName . '.phtml';

        $beforeViewRenderEvent = Kernel::dispatchEvent(new BeforeViewRenderEvent($_previewData, $viewName, $filePath));
        $_previewData = $beforeViewRenderEvent->previewData;
        $viewName = $beforeViewRenderEvent->viewName;
        $filePath = $beforeViewRenderEvent->filePath;

        Kernel::$middlewareManager->runHook('beforeViewRender', [$_previewData, $viewName, $filePath]);

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

        Kernel::dispatchEvent(new AfterViewRenderEvent($_previewData, $viewName, $filePath));
        Kernel::$middlewareManager->runHook('afterViewRender', [$_previewData, $viewName, $filePath]);
    }

}
