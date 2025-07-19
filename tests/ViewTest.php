<?php

use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\View;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    private View $view;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/view_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
        mkdir($this->tempDir . '/App/View', 0777, true);
        Kernel::$projectPath = $this->tempDir;
        $this->view = new View();
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object === "." || $object === "..") {
                continue;
            }
            $path = $dir . "/" . $object;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    public function testRenderSimpleView()
    {
        $viewFile = $this->tempDir . '/App/View/test.phtml';
        file_put_contents($viewFile, '<?php echo "Hello World"; ?>');
        ob_start();
        $this->view->render('test');
        $output = ob_get_clean();
        $this->assertStringContainsString('Hello World', $output);
    }

    public function testRenderViewWithVariables()
    {
        $viewFile = $this->tempDir . '/App/View/test.phtml';
        file_put_contents($viewFile, '<?php echo "Hello " . $name; ?>');
        ob_start();
        $this->view->render('test', ['name' => 'John']);
        $output = ob_get_clean();
        $this->assertStringContainsString('Hello John', $output);
    }

    public function testRenderViewWithMultipleVariables()
    {
        $viewFile = $this->tempDir . '/App/View/test.phtml';
        file_put_contents($viewFile, '<?php echo $greeting . " " . $name . "!"; ?>');
        ob_start();
        $this->view->render('test', [
            'greeting' => 'Hello',
            'name' => 'World'
        ]);
        $output = ob_get_clean();
        $this->assertStringContainsString('Hello World!', $output);
    }

    public function testRenderViewWithArrayVariable()
    {
        $viewFile = $this->tempDir . '/App/View/test.phtml';
        file_put_contents($viewFile, '<?php echo implode(", ", $items); ?>');
        ob_start();
        $this->view->render('test', [
            'items' => ['apple', 'banana', 'orange']
        ]);
        $output = ob_get_clean();
        $this->assertStringContainsString('apple, banana, orange', $output);
    }

    public function testRenderViewWithObjectVariable()
    {
        $viewFile = $this->tempDir . '/App/View/test.phtml';
        file_put_contents($viewFile, '<?php echo $user->name; ?>');
        $user = new stdClass();
        $user->name = 'John Doe';
        ob_start();
        $this->view->render('test', [
            'user' => $user
        ]);
        $output = ob_get_clean();
        $this->assertStringContainsString('John Doe', $output);
    }

    public function testRenderViewWithConditionalLogic()
    {
        $viewFile = $this->tempDir . '/App/View/test.phtml';
        file_put_contents($viewFile, '<?php if ($show): ?>Hello<?php else: ?>Goodbye<?php endif; ?>');
        ob_start();
        $this->view->render('test', ['show' => true]);
        $output = ob_get_clean();
        $this->assertStringContainsString('Hello', $output);
        ob_start();
        $this->view->render('test', ['show' => false]);
        $output = ob_get_clean();
        $this->assertStringContainsString('Goodbye', $output);
    }

    public function testRenderViewWithLoop()
    {
        $viewFile = $this->tempDir . '/App/View/test.phtml';
        file_put_contents($viewFile, '<?php foreach ($items as $item): echo $item . " "; endforeach; ?>');
        ob_start();
        $this->view->render('test', [
            'items' => ['a', 'b', 'c']
        ]);
        $output = ob_get_clean();
        $this->assertStringContainsString('a b c ', $output);
    }

    public function testRenderViewWithNestedDirectories()
    {
        mkdir($this->tempDir . '/App/View/admin', 0777, true);
        $viewFile = $this->tempDir . '/App/View/admin/dashboard.phtml';
        file_put_contents($viewFile, '<?php echo "Admin Dashboard"; ?>');
        ob_start();
        $this->view->render('admin/dashboard');
        $output = ob_get_clean();
        $this->assertStringContainsString('Admin Dashboard', $output);
    }

    public function testRenderViewWithHtmlContent()
    {
        $viewFile = $this->tempDir . '/App/View/test.phtml';
        file_put_contents($viewFile, '<h1><?php echo $title; ?></h1><p><?php echo $content; ?></p>');
        ob_start();
        $this->view->render('test', [
            'title' => 'My Title',
            'content' => 'My Content'
        ]);
        $output = ob_get_clean();
        $this->assertStringContainsString('<h1>My Title</h1><p>My Content</p>', $output);
    }

    public function testRenderViewWithEscapedContent()
    {
        $viewFile = $this->tempDir . '/App/View/test.phtml';
        file_put_contents($viewFile, '<?php echo htmlspecialchars($content); ?>');
        ob_start();
        $this->view->render('test', [
            'content' => '<script>alert("xss")</script>'
        ]);
        $output = ob_get_clean();
        $this->assertStringContainsString('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $output);
    }

    public function testRenderViewWithEmptyVariables()
    {
        $viewFile = $this->tempDir . '/App/View/test.phtml';
        file_put_contents($viewFile, '<?php echo $name ?? "Anonymous"; ?>');
        ob_start();
        $this->view->render('test', []);
        $output = ob_get_clean();
        $this->assertStringContainsString('Anonymous', $output);

        $viewFile = $this->tempDir . '/App/View/test.phtml';
        file_put_contents($viewFile, '<?php echo $name ?? "Anonymous"; ?>');
        ob_start();
        $this->view->render('test', ['name' => '']);
        $output = ob_get_clean();
        $this->assertStringContainsString('', $output);
    }

    public function testRenderViewWithNumericVariables()
    {
        $viewFile = $this->tempDir . '/App/View/test.phtml';
        file_put_contents($viewFile, '<?php echo $number + $another; ?>');
        ob_start();
        $this->view->render('test', [
            'number' => 5,
            'another' => 3
        ]);
        $output = ob_get_clean();
        $this->assertStringContainsString('8', $output);
    }

    public function testRenderViewWithBooleanVariables()
    {
        $viewFile = $this->tempDir . '/App/View/test.phtml';
        file_put_contents($viewFile, '<?php echo $enabled ? "ON" : "OFF"; ?>');
        ob_start();
        $this->view->render('test', ['enabled' => true]);
        $output = ob_get_clean();
        $this->assertStringContainsString('ON', $output);

        $viewFile = $this->tempDir . '/App/View/test.phtml';
        file_put_contents($viewFile, '<?php echo $enabled ? "ON" : "OFF"; ?>');
        ob_start();
        $this->view->render('test', ['enabled' => false]);
        $output = ob_get_clean();
        $this->assertStringContainsString('OFF', $output);
    }

    public function testRenderViewWithNullVariables()
    {
        $viewFile = $this->tempDir . '/App/View/test.phtml';
        file_put_contents($viewFile, '<?php echo $value ?? "default"; ?>');
        ob_start();
        $this->view->render('test', ['value' => null]);
        $output = ob_get_clean();
        $this->assertStringContainsString('default', $output);
    }

    public function testRenderViewWithLargeContent()
    {
        $largeContent = str_repeat('This is a large content. ', 1000);
        $viewFile = $this->tempDir . '/App/View/test.phtml';
        file_put_contents($viewFile, '<?php echo $content; ?>');
        ob_start();
        $this->view->render('test', ['content' => $largeContent]);
        $output = ob_get_clean();
        $this->assertStringContainsString($largeContent, $output);
    }

    public function testRenderViewWithSpecialCharacters()
    {
        $viewFile = $this->tempDir . '/App/View/test.phtml';
        file_put_contents($viewFile, '<?php echo $text; ?>');
        ob_start();
        $specialText = 'Special chars: & < > " \' \n \t \r';
        $this->view->render('test', ['text' => $specialText]);
        $output = ob_get_clean();
        $this->assertStringContainsString($specialText, $output);
    }

    public function testRenderViewWithUnicodeCharacters()
    {
        $viewFile = $this->tempDir . '/App/View/test.phtml';
        file_put_contents($viewFile, '<?php echo $text; ?>');
        ob_start();
        $this->view->render('test', ['text' => 'Unicode: ąćęłńóśźż ĄĆĘŁŃÓŚŹŻ']);
        $output = ob_get_clean();
        $this->assertStringContainsString('Unicode: ąćęłńóśźż ĄĆĘŁŃÓŚŹŻ', $output);
    }

    public function testRenderViewWithComplexDataStructure()
    {
        $viewFile = $this->tempDir . '/App/View/test.phtml';
        file_put_contents($viewFile, '<?php echo $data["user"]["name"] . " - " . $data["user"]["age"]; ?>');
        ob_start();
        $this->view->render('test', ['data' => [
            'user' => [
                'name' => 'John',
                'age' => 30
            ]
        ]]);
        $output = ob_get_clean();
        $this->assertStringContainsString('John - 30', $output);
    }

    public function testRenderViewWithFunctionCall()
    {
        $viewFile = $this->tempDir . '/App/View/test.phtml';
        file_put_contents($viewFile, '<?php echo strtoupper($text); ?>');
        ob_start();
        $this->view->render('test', ['text' => 'hello world']);
        $output = ob_get_clean();
        $this->assertStringContainsString('HELLO WORLD', $output);
    }

    public function testRenderViewWithMultiplePHPBlocks()
    {
        $viewFile = $this->tempDir . '/App/View/test.phtml';
        file_put_contents($viewFile, '<?php echo $start; ?> middle <?php echo $end; ?>');
        ob_start();
        $this->view->render('test', [
            'start' => 'BEGIN',
            'end' => 'END'
        ]);
        $output = ob_get_clean();
        $this->assertStringContainsString('BEGIN middle END', $output);
    }

    public function testRenderViewWithComments()
    {
        $viewFile = $this->tempDir . '/App/View/test.phtml';
        file_put_contents($viewFile, '<?php /* This is a comment */ echo $text; ?>');
        ob_start();
        $this->view->render('test', ['text' => 'visible']);
        $output = ob_get_clean();
        $this->assertStringContainsString('visible', $output);
    }

    public function testRenderViewWithErrorHandling()
    {
        $viewFile = $this->tempDir . '/App/View/test.phtml';
        file_put_contents($viewFile, '<?php echo $undefined_variable ?? "default"; ?>');
        ob_start();
        $this->view->render('test', []);
        $output = ob_get_clean();
        $this->assertStringContainsString('default', $output);
    }
}