<?php

namespace NimblePHP\framework\Interfaces;

/**
 * Kernel interface
 */
interface KernelInterface
{

    /**
     * Runner
     * @return void
     */
    public function handle(): void;

}