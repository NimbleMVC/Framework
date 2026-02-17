<?php

namespace NimblePHP\Framework\Module\Interfaces;

interface ModuleInterface
{

    /**
     * Get module name
     * @return string
     */
    public function getName(): string;

    /**
     * Register module
     * @return void
     */
    public function register(): void;

}