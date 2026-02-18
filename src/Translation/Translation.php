<?php

namespace NimblePHP\Framework\Translation;

use NimblePHP\Framework\Config;
use NimblePHP\Framework\Kernel;

class Translation
{

    public const PRIORITY_APP = 100;

    public const PRIORITY_MODULE = 50;

    public const PRIORITY_FRAMEWORK = 10;

    /**
     * @var Translation|null
     */
    private static ?Translation $instance = null;

    /**
     * @var array
     */
    private static array $translations = [];

    /**
     * @var array
     */
    private static array $translationPaths = [];

    /**
     * @var string
     */
    private string $currentLanguage;

    /**
     * @var string|mixed
     */
    private string $fallbackLanguage;

    /**
     * Get instances
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Create instance
     */
    private function __construct()
    {
        $this->fallbackLanguage = Config::get('TRANSLATION_FALLBACK_LANGUAGE', 'en');
        $this->registerDefaultPaths();
        $this->currentLanguage = $this->detectLanguage();
        $this->loadAllTranslations();
    }

    /**
     * Add translation from path
     * @param string $path
     * @param int $priority
     * @return void
     */
    public function addTranslationPath(string $path, int $priority = self::PRIORITY_MODULE): void
    {
        if (!is_dir($path)) {
            return;
        }

        self::$translationPaths[] = [
            'path' => $path,
            'priority' => $priority
        ];

        usort(self::$translationPaths, fn($a, $b) => $b['priority'] <=> $a['priority']);
    }

    /**
     * Translate
     * @param string $key
     * @param array $params
     * @return string
     */
    public function translate(string $key, array $params = []): string
    {
        $keys = explode('.', $key);
        $value = self::$translations;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $key;
            }
            $value = $value[$k];
        }

        if (!is_string($value)) {
            return $key;
        }

        foreach ($params as $paramKey => $paramValue) {
            $value = str_replace(':' . $paramKey, (string)$paramValue, $value);
        }

        return $value;
    }

    /**
     * Set language
     * @param string $lang
     * @return void
     */
    public function setLanguage(string $lang): void
    {
        if (!$this->languageExists($lang)) {
            return;
        }

        $this->currentLanguage = $lang;
        $this->loadAllTranslations();
        Kernel::$serviceContainer->get('kernel.cookie')->set('lang', $lang, time() + (365 * 24 * 60 * 60));
    }

    /**
     * Get current language
     * @return string
     */
    public function getCurrentLanguage(): string
    {
        return $this->currentLanguage;
    }

    /**
     * Get available languages
     * @return array
     */
    public function getAvailableLanguages(): array
    {
        $languages = [];

        foreach (self::$translationPaths as $pathData) {
            $files = glob($pathData['path'] . '/*.json');

            if ($files) {
                foreach ($files as $file) {
                    $lang = basename($file, '.json');
                    if (!in_array($lang, $languages)) {
                        $languages[] = $lang;
                    }
                }
            }
        }

        sort($languages);
        return $languages;
    }

    /**
     * Reload translations
     * @return void
     */
    public function reload(): void
    {
        self::$translations = [];
        $this->loadAllTranslations();
        $this->currentLanguage = $this->detectLanguage();
    }

    /**
     * Get transactions
     * @return array
     */
    public function getTranslations(): array
    {
        return static::$translations;
    }

    /**
     * @return string
     */
    private function detectLanguage(): string
    {
        $cookieLang = Kernel::$serviceContainer->get('kernel.cookie')->get('lang');

        if ($cookieLang && $this->languageExists($cookieLang)) {
            return $cookieLang;
        }

        $configLang = Config::get('TRANSLATION_DEFAULT_LANGUAGE', null);

        if (!empty($configLang) && $this->languageExists($configLang)) {
            return $configLang;
        }

        $browserLang = Kernel::$serviceContainer->get('kernel.request')->getBrowserLanguages();

        if (!empty($browserLang)) {
            foreach ($browserLang as $lang) {
                $lang = substr($lang, 0, 2);

                if ($this->languageExists($lang)) {
                    return $lang;
                }
            }
        }

        return $this->fallbackLanguage;
    }

    /**
     * @param string $lang
     * @return bool
     */
    private function languageExists(string $lang): bool
    {
        foreach (self::$translationPaths as $pathData) {
            if (file_exists($pathData['path'] . '/' . $lang . '.json')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return void
     */
    private function registerDefaultPaths(): void
    {
        $this->addTranslationPath(Kernel::$projectPath . '/App/Lang', self::PRIORITY_APP);
    }

    /**
     * @return void
     */
    private function loadAllTranslations(): void
    {
        self::$translations = [];

        foreach (array_reverse(self::$translationPaths) as $pathData) {
            if ($this->currentLanguage !== $this->fallbackLanguage) {
                $this->loadTranslationsFromPath($pathData['path'], $this->fallbackLanguage);
            }

            $this->loadTranslationsFromPath($pathData['path'], $this->currentLanguage);
        }
    }

    /**
     * @param string $path
     * @param string $lang
     * @return void
     */
    private function loadTranslationsFromPath(string $path, string $lang): void
    {
        $filePath = $path . '/' . $lang . '.json';

        if (!file_exists($filePath)) {
            return;
        }

        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        if (is_array($data)) {
            self::$translations = $this->arrayMergeRecursive(self::$translations, $data);
        }
    }

    /**
     * @param array $array1
     * @param array $array2
     * @return array
     */
    private function arrayMergeRecursive(array $array1, array $array2): array
    {
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($array1[$key]) && is_array($array1[$key])) {
                $array1[$key] = $this->arrayMergeRecursive($array1[$key], $value);
            } else {
                $array1[$key] = $value;
            }
        }

        return $array1;
    }

}