<?php

namespace NimblePHP\Framework\Traits;

use NimblePHP\Framework\Log;

trait LogTrait
{

    /**
     * Add log
     * @param string $message
     * @param string $level
     * @param array $content
     * @return bool
     * @throws Exception
     */
    public function log(string $message, string $level = 'INFO', array $content = []): bool
    {
        return Log::log($message, $level, $content);
    }
    
}