<?php

namespace NimblePHP\framework\CLI\Commands;

/**
 * Make controller
 */
class MakeController
{

    /**
     * Make controller
     * @param string $name
     * @return void
     */
    public function handle(string $name): void
    {
        if (empty($name)) {
            echo "Set controller name!\n";
            return;
        }

        $appPath = getcwd();
        $controllerPath = $appPath . "/App/Controller/{$name}.php";

        if (file_exists($controllerPath)) {
            echo "Controller {$controllerPath} exists\n";
            return;
        }

        file_put_contents($controllerPath, $this->template($name));
        echo "Controller {$name} created in: {$controllerPath}\n";
    }

    /**
     * Controller template
     * @param string $name
     * @return string
     */
    public function template(string $name): string
    {
        return <<<PHP
<?php

namespace App\Controllers;

use NimblePHP\\framework\Attributes\Http\Route;

class {$name}
{

    #[Route('/$name')]
    public function index(): void
    {
        echo "Hello in {$name} controller!";
    }
    
}
PHP;
    }

}