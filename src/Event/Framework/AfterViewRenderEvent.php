<?php

namespace NimblePHP\Framework\Event\Framework;

use NimblePHP\Framework\Event\AbstractEvent;

/**
 * Fired after a view response is rendered and sent.
 */
class AfterViewRenderEvent extends AbstractEvent
{

    /**
     * @param array $previewData
     * @param string $viewName
     * @param string $filePath
     */
    public function __construct(
        public array $previewData,
        public string $viewName,
        public string $filePath
    ) {
    }

}
