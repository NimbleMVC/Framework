<?php

namespace NimblePHP\Framework\Interfaces;

interface ModuleProviderInterface
{

    /**
     * Register module
     * @return void
     */
    public function register(): void;

}