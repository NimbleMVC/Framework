<?php

namespace NimblePHP\Framework\Traits;

use NimblePHP\Framework\Attributes\Http\Action;
use NimblePHP\Framework\Log;

trait LogTrait
{

    /**
     * Add log
     * @param string $message
     * @param string $level
     * @param array $content
     * @return bool
     */
    #[Action("disabled")]
    public function log(string $message, string $level = 'INFO', array $content = []): bool
    {
        return Log::log($message, $level, $content);
    }

}