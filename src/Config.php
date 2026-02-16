<?php

namespace NimblePHP\Framework;

class Config
{

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $default;
    }

}