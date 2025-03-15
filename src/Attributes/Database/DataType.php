<?php

namespace NimblePHP\Framework\Attributes\Database;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class DataType
{

    /**
     * Type
     * @var string
     */
    public string $type;

    /**
     * @param string $type
     */
    public function __construct(string $type)
    {
        $this->type = $type;
    }

}