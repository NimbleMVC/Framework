<?php

namespace NimblePHP\Framework\CLI\Commands;

use Krzysztofzylka\Console\Form;
use NimblePHP\Framework\CLI\AbstractCommand;
use NimblePHP\Framework\CLI\Attributes\ConsoleCommand;
use NimblePHP\Framework\Kernel;

/**
 * Make controller
 */
#[ConsoleCommand(
    'make:controller',
    'Create a new controller class',
    help: 'Generate a controller class inside App/Controller.',
    usage: 'php vendor/bin/nimble make:controller [name] [--route=/custom/path]',
    arguments: [
        ['name' => 'name', 'description' => 'Controller class name.', 'default' => 'prompt'],
    ],
    options: [
        ['name' => '--route', 'description' => 'Custom route path for the generated index action.'],
    ],
    examples: [
        ['command' => 'php vendor/bin/nimble make:controller Dashboard', 'description' => 'Generate App/Controller/Dashboard.php.'],
        ['command' => 'php vendor/bin/nimble make:controller Task --route=/tasks/{id}', 'description' => 'Generate a controller with a custom route attribute.'],
    ]
)]
class MakeController extends AbstractCommand
{

    public function handle(): int
    {
        $name = (string)$this->argument('name', '');
        $route = $this->normalizeRoute((string)$this->option('route', ''));

        if (empty($name)) {
            $name = Form::input('Controller name: ');

            if (empty($name)) {
                $this->output()->error('No controller name specified.');

                return 1;
            }
        }

        $name = ucfirst($name);

        $controllerPath = Kernel::$projectPath . "/App/Controller/{$name}.php";

        if (file_exists($controllerPath)) {
            $this->output()->error("Controller {$controllerPath} exists");

            return 1;
        }

        file_put_contents($controllerPath, $this->template($name, $route));
        $this->output()->success("Controller {$name} created in: {$controllerPath}");

        return 0;
    }

    /**
     * Controller template
     * @param string $name
     * @param string $route
     * @return string
     */
    public function template(string $name, string $route = ''): string
    {
        if ($route === '') {
            $routeName = $name;

            if ($routeName === 'Index') {
                $routeName = 'index';
            }

            $route = '/' . $routeName . '/index';
        }

        $routeLiteral = var_export($route, true);

        return <<<PHP
<?php

namespace App\Controller;

use NimblePHP\\Framework\Abstracts\AbstractController;
use NimblePHP\\Framework\Attributes\Http\Route;

class {$name} extends AbstractController
{

    #[Route({$routeLiteral})]
    public function index(): void
    {
        echo "Hello in {$name} controller!";
    }
    
}
PHP;
    }

    /**
     * @param string $route
     * @return string
     */
    private function normalizeRoute(string $route): string
    {
        $route = trim($route);

        if ($route === '') {
            return '';
        }

        if (!str_starts_with($route, '/')) {
            $route = '/' . $route;
        }

        return $route;
    }

}
