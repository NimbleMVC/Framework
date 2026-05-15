<?php

namespace NimblePHP\Framework\Event\Framework;

use NimblePHP\Framework\Event\AbstractEvent;

/**
 * Fired just before a resolved view file is rendered.
 */
class BeforeViewRenderEvent extends AbstractEvent
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
