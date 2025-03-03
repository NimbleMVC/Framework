<?php

namespace NimblePHP\Framework\Attributes\Database;

#[\Attribute]
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