<?php

namespace Nimblephp\framework;

use Nimblephp\framework\Interfaces\RequestInterface;
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
     */
    public function setJsonContent(array $content = [], bool $addHeader = true): void
    {
        if ($addHeader) {
            $this->addHeader('Content-Type', 'application/json');
        }

        $this->content = json_encode($content);
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
     * @param bool $flush
     * @return void
     */
    public function send(bool $flush = false): void
    {
        if ($flush) {
            ob_clean();
        }

        if (!headers_sent()) {
            header(sprintf('HTTP/1.1 %s %s', $this->statusCode, $this->statusText), true, $this->statusCode);

            foreach ($this->headers as $name => $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }

        if ($flush) {
            die($this->content);
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

}
