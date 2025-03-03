<?php

namespace NimblePHP\Framework\CLI\Commands;

use Krzysztofzylka\Console\Form;
use Krzysztofzylka\Console\Prints;
use NimblePHP\Framework\CLI\Attributes\ConsoleCommand;
use NimblePHP\Framework\Kernel;

/**
 * Make controller
 */
class MakeController
{

    #[ConsoleCommand('make:controller', 'Create a new controller class')]
    public function handle(string $name = ''): void
    {
        if (empty($name)) {
            $name = Form::input('Controller name: ');

            if (empty($name)) {
                Prints::print(value: "No controller name specified.", exit: true, color: 'red');
            }
        }

        $name = ucfirst($name);

        $controllerPath = Kernel::$projectPath . "/App/Controller/{$name}Controller.php";

        if (file_exists($controllerPath)) {
            Prints::print(value: "Controller {$controllerPath} exists", exit: true, color: 'red');
        }

        file_put_contents($controllerPath, $this->template($name));
        Prints::print(value: "Controller {$name} created in: {$controllerPath}", color: 'green');
    }

    /**
     * Controller template
     * @param string $name
     * @return string
     */
    public function template(string $name): string
    {
        $routeName = lcfirst($name);

        return <<<PHP
<?php

namespace App\Controller;

use NimblePHP\\framework\Abstracts\AbstractController;
use NimblePHP\\framework\Attributes\Http\Route;

class {$name}Controller extends AbstractController
{

    #[Route('/{$routeName}/index')]
    public function index(): void
    {
        echo "Hello in {$name} controller!";
    }
    
}
PHP;
    }

}