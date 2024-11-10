<?php

namespace Nimblephp\framework;

use DateTime;
use Exception;

/**
 * Log
 */
class Log
{

    /**
     * Session GUID
     * @var string
     */
    public static string $session;

    /**
     * Write log
     * @param string $message Log message
     * @param string $level Log level, default INFO
     * @param array $content Additional content
     * @return bool
     * @throws Exception
     */
    public static function log(string $message, string $level = 'INFO', array $content = []): bool
    {
        if ($_ENV['LOG']) {
            return false;
        }

        if (!isset(self::$session)) {
            self::$session = sprintf(
                '%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
                mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535)
            );
        }

        $backtrace = debug_backtrace()[1] ?? debug_backtrace()[0];
        $logPath = Kernel::$projectPath . '/storage/logs/' . date('Y_m_d') . '.log.json';
        $logContent = [
            'datetime' => DateTime::createFromFormat(
                'U.u',
                sprintf('%.f', microtime(true))
            )->format('Y-m-d H:i:s.u'),
            'message' => $message,
            'level' => $level,
            'content' => $content,
            'file' => $backtrace['file'] ?? null,
            'class' => $backtrace['class'] ?? null,
            'function' => $backtrace['function'] ?? null,
            'line' => $backtrace['line'] ?? null,
            'get' => $_GET,
            'session' => self::$session
        ];
        $jsonLogData = json_encode($logContent);

        if (empty(trim($jsonLogData))) {
            return false;
        }

        try {
            return (bool)file_put_contents($logPath, $jsonLogData . PHP_EOL, FILE_APPEND);
        } catch (Exception) {
            return false;
        }
    }

}