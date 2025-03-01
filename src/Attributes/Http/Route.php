<?php

namespace NimblePHP\framework\Attributes\Http;

#[\Attribute]
class Route
{

    /**
     * Path
     * @var string
     */
    public string $path;

    /**
     * Method
     * @var string
     */
    public string $method = 'GET';

    /**
     * @param string $path
     * @param string $method
     */
    public function __construct(string $path,string $method = 'GET')
    {
        $this->path = $path;
        $this->method = $method;
    }

}
