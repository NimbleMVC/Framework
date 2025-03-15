<?php

namespace NimblePHP\Framework\Interfaces;

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