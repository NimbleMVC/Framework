<?php

namespace NimblePHP\framework\Interfaces;

interface ServiceProviderInterface
{

    /**
     * Register module
     * @return void
     */
    public function register(): void;

}