<?php

namespace NimblePHP\Framework\Abstracts;

use NimblePHP\Framework\Attributes\Http\Action;
use NimblePHP\Framework\Config;
use NimblePHP\Framework\Event\Listener\ApiExceptionListener;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Exception\ValidationException;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Response;
use NimblePHP\Framework\Validation\Validator;

/**
 * Base controller for JSON APIs (e.g., SPA backends).
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
     * Hook the API exception listener and prime the response for JSON output.
     */
    #[Action('disabled')]
    public function afterConstruct(): void
    {
        $this->response = Kernel::$serviceContainer->has('kernel.response')
            ? Kernel::$serviceContainer->get('kernel.response')
            : new Response();

        $this->response->addHeader('Content-Type', 'application/json');

        ApiExceptionListener::register();
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
     * Get a single value from the JSON body, falling back to query/post.
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

    /**
     * Run an automatic paginated query on a model and send the response.
     *
     * Reads `?page=N&per_page=N` from the query string, clamps them, calls
     * AbstractModel::paginate() and emits a paginated JSON response.
     *
     * @param AbstractModel $model
     * @param array|null $condition Conditions forwarded to the model
     * @param array|null $columns Columns forwarded to the model
     * @param string|null $orderBy
     * @param string|null $groupBy
     * @param int $defaultPerPage Default when ?per_page is missing
     * @param int|null $maxPerPage Hard cap (defaults to API_PAGINATION_MAX env or 100)
     * @throws NimbleException
     */
    #[Action('disabled')]
    protected function paginate(
        AbstractModel $model,
        ?array $condition = null,
        ?array $columns = null,
        ?string $orderBy = null,
        ?string $groupBy = null,
        int $defaultPerPage = 25,
        ?int $maxPerPage = null
    ): void
    {
        $maxPerPage = $maxPerPage ?? (int)Config::get('API_PAGINATION_MAX', 100);
        $page = max(1, (int)($this->request->getQuery('page') ?? 1));
        $perPage = (int)($this->request->getQuery('per_page') ?? $defaultPerPage);
        $perPage = max(1, min($perPage, $maxPerPage));

        $result = $model->paginate($page, $perPage, $condition, $columns, $orderBy, $groupBy);

        $this->response->paginated(
            $result['items'],
            $result['total'],
            $result['page'],
            $result['per_page']
        );
    }

    /**
     * Validate the JSON request body against the given rules.
     *
     * Returns the validated data filtered to keys present in the rules
     * (whitelist – guards against mass assignment). Throws
     * ValidationException with a per-field error map on failure;
     * ApiExceptionListener converts that into a 422 JSON response.
     *
     * @param array $rules Field => rule list (same format as Validator::validate)
     * @return array Validated, whitelisted data
     * @throws ValidationException
     * @throws NimbleException
     */
    #[Action('disabled')]
    protected function validate(array $rules): array
    {
        $data = $this->json();
        Validator::validateOrFail($data, $rules);

        return array_intersect_key($data, $rules);
    }

}
