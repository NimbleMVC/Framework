<?php

namespace NimblePHP\Framework\Translation;

interface TranslationProviderInterface
{

    /**
     * Register translations
     * @return void
     */
    public function registerTranslations(): void;

}