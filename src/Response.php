<?php

namespace Nimblephp\framework;

use Nimblephp\framework\Interfaces\ResponseInterface;

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
     * @return void
     */
    public function send(): void
    {
        if (!headers_sent()) {
            header(sprintf('HTTP/1.1 %s %s', $this->statusCode, $this->statusText), true, $this->statusCode);

            foreach ($this->headers as $name => $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }

        echo $this->content;
    }

    /**
     * Redirect
     * @param string $url
     * @param int $statusCode
     * @return never
     */
    public function redirect(string $url, int $statusCode = 302): never
    {
        $request = new Request();

        if ($request->getQuery('ajax')) {
            ob_flush();
            $response = new Response();
            $response->addHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['type' => 'redirect', 'url' => '/dashboard/index']));
            $response->send();
            exit;
        }

        header('Location: ' . $url, true, $statusCode);
        exit();
    }


}