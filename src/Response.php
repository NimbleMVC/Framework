<?php

namespace NimblePHP\Framework;

use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Interfaces\RequestInterface;
use NimblePHP\Framework\Interfaces\ResponseInterface;

/**
 * Response
 */
class Response implements ResponseInterface
{

    /**
     * Content
     * @var mixed
     */
    protected mixed $content;

    /**
     * Status code
     * @var int
     */
    protected int $statusCode = 200;

    /**
     * Headers
     * @var array
     */
    protected array $headers = [];

    /**
     * Status code
     * @var string
     */
    protected string $statusText = '';

    /**
     * Request instance
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * Costructor
     */
    public function __construct()
    {
        $this->request = new Request();
    }

    /**
     * Get content
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set content
     * @param $content
     * @return void
     */
    public function setContent($content): void
    {
        $this->content = $content;
    }

    /**
     * Set json content
     * @param array $content
     * @param bool $addHeader
     * @return void
     * @throws NimbleException
     */
    public function setJsonContent(array $content = [], bool $addHeader = true): void
    {
        if ($addHeader) {
            $this->addHeader('Content-Type', 'application/json');
        }

        $jsonContent = json_encode($content);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new NimbleException('JSON encoding failed: ' . json_last_error_msg());
        }

        $this->content = $jsonContent;
    }

    /**
     * Get status code
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set status code
     * @param int $code
     * @param string $text
     * @return void
     */
    public function setStatusCode(int $code, string $text = ''): void
    {
        $this->statusCode = $code;

        if (!empty($text)) {
            $this->statusText = $text;
        }
    }

    /**
     * Add header
     * @param string $name
     * @param string $value
     * @return void
     */
    public function addHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    /**
     * Send response
     * @param bool $die
     * @return void
     */
    public function send(bool $die = false): void
    {
        if ($die) {
            ob_clean();
        }

        if (!headers_sent()) {
            header(sprintf('HTTP/1.1 %s %s', $this->statusCode, $this->statusText), true, $this->statusCode);

            foreach ($this->headers as $name => $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }

        if ($die) {
            die($this->content);
        }

        echo $this->content;
    }

    /**
     * Redirect
     * @param string $url
     * @param int $statusCode
     * @return never
     * @throws NimbleException
     */
    public function redirect(string $url, int $statusCode = 302): never
    {
        if ($this->request->isAjax()) {
            $response = new Response();
            $response->setStatusCode(200);
            $response->setJsonContent([
                'type' => 'redirect',
                'url' => $url,
            ]);
            $response->send(true);
        }

        header('Location: ' . $url, true, $statusCode);
        exit();
    }

    /**
     * Send successful API response
     * @param mixed $data Response data
     * @param int $statusCode HTTP status code
     * @param string $message Success message
     * @return void
     * @throws NimbleException
     */
    public function success(mixed $data = null, int $statusCode = 200, string $message = 'Success'): void
    {
        $this->setStatusCode($statusCode);
        $this->setJsonContent([
            'success' => true,
            'code' => $statusCode,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c'),
        ]);
        $this->send();
    }

    /**
     * Send error API response
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param mixed $data Additional error data
     * @return void
     * @throws NimbleException
     */
    public function error(string $message, int $statusCode = 400, mixed $data = null): void
    {
        $this->setStatusCode($statusCode);
        $this->setJsonContent([
            'success' => false,
            'code' => $statusCode,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c'),
        ]);
        $this->send();
    }

    /**
     * Send paginated API response
     * @param array $items Paginated items
     * @param int $total Total count
     * @param int $page Current page
     * @param int $perPage Items per page
     * @param string $message Success message
     * @return void
     * @throws NimbleException
     */
    public function paginated(array $items, int $total, int $page, int $perPage, string $message = 'Success'): void
    {
        $this->setStatusCode(200);
        $this->setJsonContent([
            'success' => true,
            'code' => 200,
            'message' => $message,
            'data' => $items,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'pages' => ceil($total / $perPage),
            ],
            'timestamp' => date('c'),
        ]);
        $this->send();
    }

    /**
     * Send created response (201)
     * @param mixed $data Created resource data
     * @param string $message Success message
     * @return void
     * @throws NimbleException
     */
    public function created(mixed $data, string $message = 'Resource created'): void
    {
        $this->success($data, 201, $message);
    }

    /**
     * Send no content response (204)
     * @return void
     * @throws NimbleException
     */
    public function noContent(): void
    {
        $this->setStatusCode(204);
        $this->setContent('');
        $this->send();
    }

}