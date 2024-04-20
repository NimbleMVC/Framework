<?php

namespace Nimblephp\framework\Interfaces;

use Nimblephp\framework\Exception\NotFoundException;

/**
 * View interface
 */
interface ViewInterface
{

    /**
     * Render view
     * @param string $viewName
     * @param array $data
     * @return void
     * @throws NotFoundException
     */
    public function render(string $viewName, array $data = []): void;

}