<?php

namespace NimblePHP\Framework\Attributes\DependencyInjection;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Inject
{

    public string $className;

    public function __construct(string $className) {
        $this->className = $className;
    }

}