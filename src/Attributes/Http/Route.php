<?php

namespace NimblePHP\Framework\Attributes\Http;

/**
 * Routing
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
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
