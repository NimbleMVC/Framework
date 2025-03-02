<?php

namespace NimblePHP\framework\CLI\Commands;

use Krzysztofzylka\Console\Form;
use Krzysztofzylka\Console\Prints;

/**
 * Make controller
 */
class MakeController
{

    public static string $description = "Create a new controller class";

    /**
     * Make controller
     * @param string $name
     * @return void
     */
    public function handle(string $name = ''): void
    {
        if (empty($name)) {
            $name = Form::input('Set controller name:');
        }

        if (empty($name)) {
            Prints::print(value: 'Controller name cannot be empty!', exit: true, color: 'red');
        }

        $appPath = getcwd();
        $controllerPath = $appPath . "/App/Controller/{$name}.php";

        if (file_exists($controllerPath)) {
            Prints::print(value: "Controller {$controllerPath} exists", exit: true, color: 'red');
        }

        file_put_contents($controllerPath, $this->template($name));
        Prints::print(value: "Controller {$name} created in: {$controllerPath}", exit: true, color: 'green');
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

namespace App\Controller;

use NimblePHP\\framework\Abstracts\AbstractController;
use NimblePHP\\framework\Attributes\Http\Route;

class {$name} extends AbstractController
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