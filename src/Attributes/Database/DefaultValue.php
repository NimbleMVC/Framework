<?php

namespace NimblePHP\Framework\Attributes\Database;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class DefaultValue
{

    /**
     * Value
     * @var string
     */
    public string $value;

    /**
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

}