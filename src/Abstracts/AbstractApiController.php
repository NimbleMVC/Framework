<?php

namespace NimblePHP\Framework\Abstracts;

use NimblePHP\Framework\Attributes\Http\Action;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Middleware\ApiExceptionHandler;
use NimblePHP\Framework\Response;

/**
 * Base controller for JSON APIs (e.g. SPA backends).
 * Provides JSON body parsing, automatic Content-Type header, and JSON
 * exception handling. All public methods that are reachable as routes
 * should send their response via $this->success/error/created/etc.
 */
abstract class AbstractApiController extends AbstractController
{

    /**
     * Response instance used to emit JSON.
     */
    public Response $response;

    /**
     * Decoded JSON request body cache.
     */
    protected ?array $jsonBodyCache = null;

    /**
     * Hook the API exception handler and prime the response for JSON output.
     */
    #[Action('disabled')]
    public function afterConstruct(): void
    {
        $this->response = Kernel::$serviceContainer->has('kernel.response')
            ? Kernel::$serviceContainer->get('kernel.response')
            : new Response();

        $this->response->addHeader('Content-Type', 'application/json');

        ApiExceptionHandler::register();
    }

    /**
     * Read and decode the JSON request body.
     *
     * @throws NimbleException when the body is not valid JSON
     */
    #[Action('disabled')]
    protected function json(): array
    {
        if ($this->jsonBodyCache !== null) {
            return $this->jsonBodyCache;
        }

        $body = $this->request->getBody();

        if ($body === '') {
            return $this->jsonBodyCache = [];
        }

        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new NimbleException('Invalid JSON body: ' . json_last_error_msg(), 400);
        }

        if (!is_array($decoded)) {
            throw new NimbleException('JSON body must decode to an object or array', 400);
        }

        return $this->jsonBodyCache = $decoded;
    }

    /**
     * Get a single value from JSON body, falling back to query/post.
     * @throws NimbleException
     */
    #[Action('disabled')]
    protected function input(string $key, mixed $default = null): mixed
    {
        $data = $this->json();

        if (array_key_exists($key, $data)) {
            return $data[$key];
        }

        return $this->request->getPost($key) ?? $this->request->getQuery($key) ?? $default;
    }

    /**
     * Send a success response.
     * @throws NimbleException
     */
    #[Action('disabled')]
    protected function success(mixed $data = null, int $statusCode = 200, string $message = 'Success'): void
    {
        $this->response->success($data, $statusCode, $message);
    }

    /**
     * Send an error response.
     * @throws NimbleException
     */
    #[Action('disabled')]
    protected function error(string $message, int $statusCode = 400, mixed $data = null): void
    {
        $this->response->error($message, $statusCode, $data);
    }

    /**
     * Send a 201 Created response.
     * @throws NimbleException
     */
    #[Action('disabled')]
    protected function created(mixed $data, string $message = 'Resource created'): void
    {
        $this->response->created($data, $message);
    }

    /**
     * Send a 204 No Content response.
     */
    #[Action('disabled')]
    protected function noContent(): void
    {
        $this->response->noContent();
    }

    /**
     * Send a paginated response.
     * @throws NimbleException
     */
    #[Action('disabled')]
    protected function paginated(array $items, int $total, int $page, int $perPage, string $message = 'Success'): void
    {
        $this->response->paginated($items, $total, $page, $perPage, $message);
    }

}
