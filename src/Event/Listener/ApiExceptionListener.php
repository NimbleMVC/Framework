<?php

namespace NimblePHP\Framework\Event\Listener;

use JetBrains\PhpStorm\NoReturn;
use NimblePHP\Framework\Config;
use NimblePHP\Framework\Event\Framework\ExceptionEvent;
use NimblePHP\Framework\Exception\HiddenException;
use NimblePHP\Framework\Exception\NotFoundException;
use NimblePHP\Framework\Exception\ValidationException;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Log;
use Throwable;

/**
 * Convert unhandled API exceptions into JSON responses.
 */
class ApiExceptionListener
{

    /**
     * Whether the listener is already registered with the event dispatcher.
     */
    protected static bool $registered = false;

    /**
     * Register the listener with the kernel event dispatcher.
     *
     * @return void
     */
    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        Kernel::getEventDispatcher()->addListener(ExceptionEvent::class, new self(), 100);
        self::$registered = true;
    }

    /**
     * Reset the registration flag used by tests and repeated bootstrap.
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$registered = false;
    }

    /**
     * Handle the framework exception event.
     *
     * @param ExceptionEvent $event
     * @return void
     */
    #[NoReturn]
    public function handle(ExceptionEvent $event): void
    {
        $this->renderException($event->exception);
    }

    /**
     * Render an exception as a JSON API error response.
     *
     * @param Throwable $exception
     * @return void
     */
    #[NoReturn]
    protected function renderException(Throwable $exception): void
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

        if ($exception instanceof ValidationException && $exception->getFieldErrors()) {
            $payload['errors'] = $exception->getFieldErrors();
        }

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
     *
     * @param Throwable $exception
     * @return int
     */
    protected function resolveStatusCode(Throwable $exception): int
    {
        if ($exception instanceof NotFoundException) {
            return 404;
        }

        if ($exception instanceof ValidationException && $exception->getFieldErrors()) {
            return 422;
        }

        $code = $exception->getCode();

        return ($code >= 400 && $code < 600) ? $code : 500;
    }

}
