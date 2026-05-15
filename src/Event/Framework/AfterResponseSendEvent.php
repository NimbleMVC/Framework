<?php

namespace NimblePHP\Framework\Event\Framework;

use NimblePHP\Framework\Event\AbstractEvent;
use NimblePHP\Framework\Response;

/**
 * Fired after a response sends its headers and body.
 */
class AfterResponseSendEvent extends AbstractEvent
{

    /**
     * @param Response $response
     * @param mixed $content
     * @param int $statusCode
     * @param string $statusText
     * @param array $headers
     * @param bool $die
     */
    public function __construct(
        public Response $response,
        public mixed $content,
        public int $statusCode,
        public string $statusText,
        public array $headers,
        public bool $die = false
    ) {
    }

}
