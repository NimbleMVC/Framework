<?php

namespace NimblePHP\Framework\Abstracts\Middlewares;

use NimblePHP\Framework\Interfaces\ResponseInterface;
use NimblePHP\Framework\Interfaces\ResponseMiddlewareInterface;

 class AbstractResponseMiddleware implements ResponseMiddlewareInterface
{

    /**
     * Before send
     * @param ResponseInterface &$response
     * @return void
     */
    public function beforeSend(ResponseInterface &$response): void {}

    /**
     * After send
     * @param ResponseInterface $response
     * @return void
     */
    public function afterSend(ResponseInterface $response): void {}

    /**
     * Before set content
     * @param ResponseInterface &$response
     * @param mixed &$content
     * @return void
     */
    public function beforeSetContent(ResponseInterface &$response, &$content): void {}

    /**
     * After set content
     * @param ResponseInterface $response
     * @param mixed $content
     * @return void
     */
    public function afterSetContent(ResponseInterface $response, $content): void {}

    /**
     * Before set header
     * @param ResponseInterface &$response
     * @param string $key
     * @param mixed &$value
     * @return void
     */
    public function beforeSetHeader(ResponseInterface &$response, string $key, &$value): void {}

    /**
     * After set header
     * @param ResponseInterface $response
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function afterSetHeader(ResponseInterface $response, string $key, $value): void {}

    /**
     * Before set status code
     * @param ResponseInterface &$response
     * @param mixed &$statusCode
     * @return void
     */
    public function beforeSetStatusCode(ResponseInterface &$response, &$statusCode): void {}

    /**
     * After set status code
     * @param ResponseInterface $response
     * @param mixed $statusCode
     * @return void
     */
    public function afterSetStatusCode(ResponseInterface $response, $statusCode): void {}

}