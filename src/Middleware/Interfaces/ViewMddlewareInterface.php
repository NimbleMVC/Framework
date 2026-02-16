<?php

namespace NimblePHP\Framework\Middleware\Interfaces;

interface ViewMddlewareInterface
{

    public function processingViewData(array &$data): void;

    public function beforeViewRender(array $data, string $viewName, string $filePath): void;

    public function afterviewRender(array $data, string $viewName, string $filePath): void;

}