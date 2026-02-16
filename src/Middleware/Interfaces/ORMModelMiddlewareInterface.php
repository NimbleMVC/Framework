<?php

namespace NimblePHP\Framework\Middleware\Interfaces;

use NimblePHP\Framework\Interfaces\ORMModelInterface;

interface ORMModelMiddlewareInterface
{

    public function afterConstructORMModel(ORMModelInterface $model): void;

}