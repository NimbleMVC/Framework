<?php

namespace NimblePHP\Framework\Middleware\Interfaces;

interface ServiceMddlewareInterface
{

    public function serviceGet(string $id): void;

    public function serviceHas(string $id): void;

    public function serviceRemove(string $id): void;

    public function serviceSet(string $id, mixed $service): void;

}