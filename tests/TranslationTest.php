<?php

use NimblePHP\Framework\Container\ServiceContainer;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Middleware\MiddlewareManager;
use NimblePHP\Framework\Request;
use NimblePHP\Framework\Translation\Translation;
use PHPUnit\Framework\TestCase;

class TranslationTest extends TestCase
{
    private array $envBackup;

    private array $serverBackup;

    private string $projectPath;

    private TranslationTestCookie $cookie;

    protected function setUp(): void
    {
        $this->envBackup = $_ENV;
        $this->serverBackup = $_SERVER;
        $this->projectPath = sys_get_temp_dir() . '/nimble_translation_' . uniqid('', true);
        mkdir($this->projectPath . '/App/Lang', 0777, true);

        Kernel::$projectPath = $this->projectPath;
        Kernel::$middlewareManager = new MiddlewareManager();
        Kernel::$serviceContainer = new ServiceContainer();

        $this->cookie = new TranslationTestCookie();
        Kernel::$serviceContainer->set('kernel.cookie', $this->cookie);
        Kernel::$serviceContainer->set('kernel.request', new Request());

        $this->resetTranslationState();
    }

    protected function tearDown(): void
    {
        $_ENV = $this->envBackup;
        $_SERVER = $this->serverBackup;
        $this->resetTranslationState();
        $this->removeDirectory($this->projectPath);
    }

    public function testLoadsDefaultLanguageAndTranslatesNestedKeys(): void
    {
        $_ENV['TRANSLATION_DEFAULT_LANGUAGE'] = 'en';
        $_ENV['TRANSLATION_FALLBACK_LANGUAGE'] = 'en';

        $this->writeLangFile($this->projectPath . '/App/Lang/en.json', [
            'messages' => [
                'welcome' => 'Hello',
            ],
        ]);

        $translation = Translation::getInstance();

        $this->assertSame('en', $translation->getCurrentLanguage());
        $this->assertSame('Hello', $translation->translate('messages.welcome'));
        $this->assertSame('messages.missing', $translation->translate('messages.missing'));
    }

    public function testDetectsBrowserLanguageAndInterpolatesParameters(): void
    {
        $_ENV['TRANSLATION_FALLBACK_LANGUAGE'] = 'en';
        unset($_ENV['TRANSLATION_DEFAULT_LANGUAGE']);
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'pl-PL, en;q=0.8';
        Kernel::$serviceContainer->set('kernel.request', new Request());

        $this->writeLangFile($this->projectPath . '/App/Lang/en.json', [
            'greeting' => 'Hello :name',
        ]);
        $this->writeLangFile($this->projectPath . '/App/Lang/pl.json', [
            'greeting' => 'Czesc :name',
        ]);

        $translation = Translation::getInstance();

        $this->assertSame('pl', $translation->getCurrentLanguage());
        $this->assertSame('Czesc Jan', $translation->translate('greeting', ['name' => 'Jan']));
    }

    public function testHigherPriorityPathsOverrideLowerPriorityTranslations(): void
    {
        $_ENV['TRANSLATION_DEFAULT_LANGUAGE'] = 'en';
        $_ENV['TRANSLATION_FALLBACK_LANGUAGE'] = 'en';

        $this->writeLangFile($this->projectPath . '/App/Lang/en.json', [
            'shared' => 'app',
        ]);

        $modulePath = $this->projectPath . '/Module/Lang';
        mkdir($modulePath, 0777, true);
        $this->writeLangFile($modulePath . '/en.json', [
            'shared' => 'module',
            'module_only' => 'from module',
        ]);

        $translation = Translation::getInstance();
        $translation->addTranslationPath($modulePath, Translation::PRIORITY_MODULE);
        $translation->reload();

        $this->assertSame('app', $translation->translate('shared'));
        $this->assertSame('from module', $translation->translate('module_only'));
    }

    public function testSetLanguageUpdatesCookieOnlyForExistingLanguage(): void
    {
        $_ENV['TRANSLATION_DEFAULT_LANGUAGE'] = 'en';
        $_ENV['TRANSLATION_FALLBACK_LANGUAGE'] = 'en';

        $this->writeLangFile($this->projectPath . '/App/Lang/en.json', ['label' => 'English']);
        $this->writeLangFile($this->projectPath . '/App/Lang/pl.json', ['label' => 'Polski']);

        $translation = Translation::getInstance();
        $translation->setLanguage('pl');

        $this->assertSame('pl', $translation->getCurrentLanguage());
        $this->assertSame('lang', $this->cookie->lastSet['name']);
        $this->assertSame('pl', $this->cookie->lastSet['value']);

        $translation->setLanguage('de');

        $this->assertSame('pl', $translation->getCurrentLanguage());
        $this->assertSame('pl', $this->cookie->lastSet['value']);
    }

    public function testGetAvailableLanguagesReturnsSortedUniqueCodes(): void
    {
        $_ENV['TRANSLATION_DEFAULT_LANGUAGE'] = 'en';
        $_ENV['TRANSLATION_FALLBACK_LANGUAGE'] = 'en';

        $this->writeLangFile($this->projectPath . '/App/Lang/en.json', ['a' => '1']);
        $this->writeLangFile($this->projectPath . '/App/Lang/pl.json', ['a' => '2']);

        $modulePath = $this->projectPath . '/Module/Lang';
        mkdir($modulePath, 0777, true);
        $this->writeLangFile($modulePath . '/en.json', ['a' => '3']);
        $this->writeLangFile($modulePath . '/de.json', ['a' => '4']);

        $translation = Translation::getInstance();
        $translation->addTranslationPath($modulePath, Translation::PRIORITY_MODULE);

        $this->assertSame(['de', 'en', 'pl'], $translation->getAvailableLanguages());
    }

    private function writeLangFile(string $path, array $payload): void
    {
        file_put_contents($path, json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function resetTranslationState(): void
    {
        $reflection = new ReflectionClass(Translation::class);

        foreach (['instance' => null, 'translations' => [], 'translationPaths' => []] as $propertyName => $value) {
            $property = $reflection->getProperty($propertyName);
            $property->setAccessible(true);
            $property->setValue(null, $value);
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}

class TranslationTestCookie
{
    public array $values = [];

    public array $lastSet = [];

    public static function setSameSite(string $sameSite): void
    {
    }

    public static function setDefaultSecure(bool $defaultSecure): void
    {
    }

    public static function setDefaultHttpOnly(bool $defaultHttpOnly): void
    {
    }

    public function set(
        string $name,
        mixed $value,
        int $expire = 0,
        string $path = '/',
        string $domain = '',
        ?bool $secure = null,
        ?bool $httponly = false
    ): void
    {
        $this->values[$name] = $value;
        $this->lastSet = [
            'name' => $name,
            'value' => $value,
            'expire' => $expire,
        ];
    }

    public function get($name): mixed
    {
        return $this->values[$name] ?? null;
    }

    public function exists($name): bool
    {
        return array_key_exists($name, $this->values);
    }

    public function remove($name): void
    {
        unset($this->values[$name]);
    }
}
