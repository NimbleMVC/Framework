<?php

namespace NimblePHP\Framework\Attributes\DependencyInjection;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Inject
{

    /**
     * Class name
     * @var string 
     */
    public string $className;

    /**
     * @param string $className
     */
    public function __construct(string $className)
    {
        $this->className = $className;
    }

}