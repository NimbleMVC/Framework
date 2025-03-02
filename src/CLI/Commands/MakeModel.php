<?php

namespace NimblePHP\framework\CLI\Commands;

class MakeModel
{

    public function handle(string $name = ''): void
    {
        if (empty($name)) {
            echo "Write model name!\n";
            return;
        }

        $appPath = getcwd();
        $path = $appPath . "/App/Model/{$name}.php";

        if (file_exists($path)) {
            echo "Model already exists!\n";
            return;
        }

        $template = <<<PHP
<?php

namespace App\Model;

use NimblePHP\\framework\Abstracts\AbstractModel;

class {$name} extends AbstractModel {
}
PHP;

        file_put_contents($path, $template);
        echo "Model {$name} created!\n";
    }

}