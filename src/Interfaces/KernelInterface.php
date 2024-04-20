<?php

namespace Nimblephp\framework\Interfaces;

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