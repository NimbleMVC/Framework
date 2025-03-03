<?php

namespace NimblePHP\Framework\Interfaces;

interface ServiceProviderInterface
{

    /**
     * Register module
     * @return void
     */
    public function register(): void;

}