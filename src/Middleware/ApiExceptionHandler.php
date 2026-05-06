<?php

namespace NimblePHP\Framework\Middleware;

use NimblePHP\Framework\Config;
use NimblePHP\Framework\Exception\HiddenException;
use NimblePHP\Framework\Exception\NotFoundException;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Log;
use Throwable;

/**
 * Converts unhandled exceptions into JSON responses for API controllers.
 */
class ApiExceptionHandler
{

    /**
     * Whether the handler is already registered with the middleware manager.
     */
    protected static bool $registered = false;

    /**
     * Register the handler with the kernel middleware manager (idempotent).
     */
    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        Kernel::$middlewareManager->add(new self(), 100);
        self::$registered = true;
    }

    /**
     * Reset registration flag (useful for tests).
     */
    public static function reset(): void
    {
        self::$registered = false;
    }

    /**
     * Exception hook invoked by the kernel when a Throwable bubbles up.
     */
    public function exceptionHook(Throwable $exception): void
    {
        $statusCode = $this->resolveStatusCode($exception);
        $publicMessage = $exception->getMessage();
        $internalMessage = $publicMessage;

        if ($exception instanceof HiddenException) {
            $internalMessage = $exception->getHiddenMessage();
        }

        Log::log($internalMessage, 'API_ERR', [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'backtrace' => $exception->getTraceAsString(),
        ]);

        if (headers_sent()) {
            exit(1);
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code($statusCode);
        header('Content-Type: application/json');

        $payload = [
            'success' => false,
            'code' => $statusCode,
            'message' => $publicMessage,
            'data' => null,
            'timestamp' => date('c'),
        ];

        if (filter_var(Config::get('DEBUG', false), FILTER_VALIDATE_BOOLEAN)) {
            $payload['debug'] = [
                'exception' => $exception::class,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => array_slice($exception->getTrace(), 0, 10),
            ];
        }

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Map an exception to an HTTP status code.
     */
    protected function resolveStatusCode(Throwable $exception): int
    {
        if ($exception instanceof NotFoundException) {
            return 404;
        }

        $code = $exception->getCode();

        return ($code >= 400 && $code < 600) ? $code : 500;
    }

}
