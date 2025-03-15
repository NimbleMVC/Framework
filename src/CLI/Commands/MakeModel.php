<?php

namespace NimblePHP\Framework\CLI\Commands;

use Krzysztofzylka\Console\Form;
use Krzysztofzylka\Console\Prints;
use NimblePHP\Framework\CLI\Attributes\ConsoleCommand;
use NimblePHP\Framework\Kernel;

class MakeModel
{

    #[ConsoleCommand('make:model', 'Create a new controller class')]
    public function handle(string $name = ''): void
    {
        if (empty($name)) {
            $name = Form::input('Model name: ');

            if (empty($name)) {
                Prints::print(value: "No model name specified.", exit: true, color: 'red');
            }
        }

        $name = ucfirst($name);

        $path = Kernel::$projectPath . "/App/Model/{$name}.php";

        if (file_exists($path)) {
            Prints::print(value: "Model already exists.", exit: true, color: 'red');
            return;
        }

        $template = <<<PHP
<?php

namespace App\Model;

use NimblePHP\\Framework\Abstracts\AbstractModel;

class {$name} extends AbstractModel {
}
PHP;

        file_put_contents($path, $template);
        Prints::print(value: "Model {$name} created", exit: true, color: 'green');
    }

}