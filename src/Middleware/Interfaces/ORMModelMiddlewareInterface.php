<?php

namespace NimblePHP\Framework\Middleware\Interfaces;

use NimblePHP\Framework\Interfaces\ORMModelInterface;

/**
 * @deprecated Use typed framework events instead.
 */
interface ORMModelMiddlewareInterface
{

    public function afterConstructORMModel(ORMModelInterface $model): void;

}
