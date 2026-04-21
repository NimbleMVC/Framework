<?php

namespace NimblePHP\Framework\CLI\Commands;

use Krzysztofzylka\Console\Form;
use NimblePHP\Framework\CLI\AbstractCommand;
use NimblePHP\Framework\CLI\Attributes\ConsoleCommand;
use NimblePHP\Framework\Kernel;

#[ConsoleCommand(
    'make:model',
    'Create a new model class',
    help: 'Generate a model class inside App/Model.',
    usage: 'php vendor/bin/nimble make:model [name]',
    arguments: [
        ['name' => 'name', 'description' => 'Model class name.', 'default' => 'prompt'],
    ],
    examples: [
        ['command' => 'php vendor/bin/nimble make:model Task', 'description' => 'Generate App/Model/TaskModel.php.'],
        ['command' => 'php vendor/bin/nimble make:model task_item', 'description' => 'Generate App/Model/TaskItemModel.php.'],
    ]
)]
class MakeModel extends AbstractCommand
{

    public function handle(): int
    {
        $name = (string)$this->argument('name', '');

        if (empty($name)) {
            $name = Form::input('Model name: ');

            if (empty($name)) {
                $this->output()->error('No model name specified.');

                return 1;
            }
        }

        $name = $this->normalizeModelName($name);

        $path = Kernel::$projectPath . "/App/Model/{$name}.php";

        if (file_exists($path)) {
            $this->output()->error('Model already exists.');

            return 1;
        }

        $template = <<<PHP
<?php

namespace App\Model;

use NimblePHP\\Framework\Abstracts\AbstractModel;

class {$name} extends AbstractModel {
}
PHP;

        file_put_contents($path, $template);
        $this->output()->success("Model {$name} created");

        return 0;
    }

    /**
     * @param string $name
     * @return string
     */
    private function normalizeModelName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/[^a-zA-Z0-9]+/', ' ', $name) ?? $name;
        $name = preg_replace('/(?<!^)([A-Z])/', ' $1', $name) ?? $name;
        $name = ucwords(strtolower(trim($name)));
        $name = str_replace(' ', '', $name);
        $name = preg_replace('/Model$/i', '', $name) ?? $name;

        return $name . 'Model';
    }

}
