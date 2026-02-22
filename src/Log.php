<?php

namespace NimblePHP\Framework;

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
     * Storage instance
     * @var Storage
     */
    public static Storage $storage;

    /**
     * Initialize static variable
     * @return void
     */
    public static function init(): void
    {
        if (!isset(self::$session)) {
            self::generateSession();
        }

        if (!isset(self::$storage)) {
            self::$storage = new Storage('logs');
        }
    }

    /**
     * Write log
     * @param string $message Log message
     * @param string $level Log level, default INFO
     * @param array $content Additional content
     * @return bool
     */
    public static function log(string $message, string $level = 'INFO', array $content = []): bool
    {
        if (!($_ENV['LOG'] ?? false)) {
            return false;
        }

        if (isset(Kernel::$middlewareManager)) {
            Kernel::$middlewareManager->runHookWithReference('beforeLog', $message);
        }

        $level = strtoupper($level);

        if ($level === 'ERR') {
            $level = 'ERROR';
        } elseif ($level === 'FATAL_ERR' || $level === 'FATAL_ERROR') {
            $level = 'CRITICAL';
        }

        $allowedLevels = ['DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL'];

        if (!in_array($level, $allowedLevels)) {
            $level = 'INFO';
        }

        try {
            self::init();

            $backtrace = self::getBacktrace();

            $logContent = [
                'datetime' => self::getDatetime(),
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

            if (isset(Kernel::$middlewareManager)) {
                Kernel::$middlewareManager->runHookWithReference('afterLog', $logContent);
            }

            $jsonLogData = json_encode($logContent);

            if ($jsonLogData === false || empty(trim($jsonLogData))) {
                return false;
            }

            $filename = date('Y_m_d') . '.log.json';
            $return = self::$storage->append($filename, $jsonLogData);

            if (mt_rand(1, 100) === 1) {
                self::rotateLogs($filename);
            }

            return $return;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Generate log session
     * @return void
     */
    public static function generateSession(): void
    {
        self::$session = sprintf(
            '%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(16384, 20479),
            mt_rand(32768, 49151),
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(0, 65535)
        );
    }

    /**
     * Rotate logs
     * @param string $currentFile
     * @return void
     */
    private static function rotateLogs(string $currentFile): void
    {
        $maxSize = 10 * 1024 * 1024;
        $maxFiles = 30;

        $filePath = self::$storage->getPath() . '/' . $currentFile;

        if (file_exists($filePath) && filesize($filePath) > $maxSize) {
            $backupFile = $filePath . '.' . time();
            rename($filePath, $backupFile);
        }

        $logFiles = glob(self::$storage->getPath() . '/*.log.json.*');
        if (count($logFiles) > $maxFiles) {
            usort($logFiles, function ($a, $b) {
                return filemtime($a) - filemtime($b);
            });

            $filesToDelete = array_slice($logFiles, 0, count($logFiles) - $maxFiles);
            foreach ($filesToDelete as $file) {
                unlink($file);
            }
        }
    }

    /**
     * Get actual datetime
     * @return string
     */
    private static function getDatetime(): string
    {
        return DateTime::createFromFormat(
            'U.u',
            sprintf('%.f', microtime(true))
        )->format('Y-m-d H:i:s.u');
    }

    /**
     * Get backtrace
     * @return array
     */
    private static function getBacktrace(): array
    {
        $next = false;

        foreach (debug_backtrace() as $backtrace) {
            if (!$next && $backtrace['function'] === 'log') {
                $next = true;
            } elseif ($next) {
                return $backtrace;
            }
        }

        return [];
    }

}