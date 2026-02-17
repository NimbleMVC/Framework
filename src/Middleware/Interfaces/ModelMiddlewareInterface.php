<?php

namespace NimblePHP\Framework\Middleware\Interfaces;

use NimblePHP\Framework\Interfaces\ModelInterface;

interface ModelMiddlewareInterface
{

    public function afterConstructModel(ModelInterface $model): void;

    public function processingModelData(array &$data): void;

    public function processingModelQuery(array &$data): void;

}