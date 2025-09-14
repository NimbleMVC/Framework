<?php

namespace NimblePHP\Framework\Interfaces;

interface ModuleProviderUpdateInterface
{

    /**
     * On update method
     * @return void
     */
    public function onUpdate(): void;

}