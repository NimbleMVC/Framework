<?php

namespace NimblePHP\Framework\Interfaces;

interface ServiceProviderUpdateInterface
{

    /**
     * On update method
     * @return void
     */
    public function onUpdate(): void;

}