<?php

namespace Nimblephp\framework\Interfaces;

interface ServiceProviderInterface
{

    /**
     * Register module
     * @return void
     */
    public function register(): void;

}