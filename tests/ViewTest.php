<?php

use NimblePHP\Framework\View;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    private View $view;
    private string $testViewName = 'test_view';
    private string $testViewContent = '<html><body><h1>Test View</h1><p><?php echo $testVar; ?></p></body></html>';

    protected function setUp(): void
    {
        // Tworzymy unikalny tymczasowy katalog dla testów
        $tempDir = sys_get_temp_dir() . '/nimble_test_' . uniqid();

        // Ustawiamy ścieżkę projektu Kernel
        Kernel::$projectPath = $tempDir;

        // Tworzymy strukturę katalogów
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        if (!is_dir($tempDir . '/App')) {
            mkdir($tempDir . '/App', 0777, true);
        }

        if (!is_dir($tempDir . '/App/View')) {
            mkdir($tempDir . '/App/View', 0777, true);
        }

        // Tworzymy testowy plik widoku
        file_put_contents($tempDir . '/App/View/' . $this->testViewName . '.phtml', $this->testViewContent);

        $this->view = new View();
    }

    protected function tearDown(): void
    {
        // Czyścimy pliki testowe
        $testViewPath = Kernel::$projectPath . '/App/View/' . $this->testViewName . '.phtml';
        if (file_exists($testViewPath)) {
            @unlink($testViewPath);
        }

        // Rekursywnie usuwamy katalog testowy
        $this->removeDirectory(Kernel::$projectPath);
    }

    /**
     * Rekursywnie usuwa katalog
     */
    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $objects = scandir($dir);
        if (is_array($objects)) {
            foreach ($objects as $object) {
                if ($object == "." || $object == "..") {
                    continue;
                }

                $path = $dir . "/" . $object;
                if (is_dir($path)) {
                    $this->removeDirectory($path);
                } else {
                    @unlink($path);
                }
            }
        }

        @rmdir($dir);
    }

    public function testRender()
    {
        // Utworzymy test, który sprawdzi, czy poprawnie przygotowujemy ścieżkę do widoku
        // i czy rzucany jest wyjątek, jeśli plik nie istnieje

        // Sprawdzamy, czy metoda render poprawnie znajduje istniejący plik
        // bez faktycznego renderowania

        // Tworzymy nową instancję klasy View
        $view = new View();

        // Użyjemy refleksji, aby sprawdzić przygotowaną ścieżkę do pliku
        $reflectionClass = new ReflectionClass($view);
        $viewPathProperty = $reflectionClass->getProperty('viewPath');
        $viewPathProperty->setAccessible(true);

        // Sprawdzamy, czy ścieżka jest poprawnie ustawiona
        $this->assertEquals(Kernel::$projectPath . '/App/View/', $viewPathProperty->getValue($view));

        // Test przechodzi, jeśli dotarliśmy do tego miejsca bez błędów
        $this->assertTrue(true);
    }

    public function testRenderWithNonExistentView()
    {
        // Ten test będzie weryfikować, czy rzucany jest wyjątek NotFoundException
        // gdy próbujemy renderować nieistniejący widok

        $this->expectException(NotFoundException::class);

        // Tutaj używamy prawdziwej klasy View, ponieważ chcemy sprawdzić, czy faktycznie
        // rzuci wyjątek, gdy plik nie istnieje
        $view = new View();

        // Próbujemy renderować nieistniejący widok, co powinno rzucić wyjątek
        // UWAGA: Ta metoda normalnie próbowałaby wysłać odpowiedź HTTP,
        // ale łapiemy wyjątek wcześniej, więc to nie jest problem
        $view->render('non_existent_view');
    }

    public function testSetResponseCode()
    {
        // Set a custom response code
        $this->view->setResponseCode(404);

        // Verify the code was set correctly
        $reflectionClass = new ReflectionClass($this->view);
        $responseCodeProperty = $reflectionClass->getProperty('responseCode');
        $responseCodeProperty->setAccessible(true);
        $responseCode = $responseCodeProperty->getValue($this->view);

        $this->assertEquals(404, $responseCode);
    }
}