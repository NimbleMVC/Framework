<?php

namespace NimblePHP\framework\Attributes\Database;

#[\Attribute]
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