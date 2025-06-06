<?php

namespace NimblePHP\Framework\CLI\Commands;

use Krzysztofzylka\Console\Form;
use Krzysztofzylka\Console\Prints;
use Krzysztofzylka\File\File;
use NimblePHP\Framework\CLI\Attributes\ConsoleCommand;
use NimblePHP\Framework\CLI\ConsoleHelper;
use NimblePHP\Framework\Kernel;


class Project
{

    #[ConsoleCommand('project:structure', 'Project structure')]
    public function projectStructure(string $directory = ''): void
    {
        passthru("tree -L 2 " . getcwd());
    }

    #[ConsoleCommand('project:size', 'Project size')]
    public function projectSize(): void
    {
        passthru("du -sh .");
    }

    #[ConsoleCommand('project:init', 'Project initialization')]
    public function projectInit(string $directory = ''): void
    {
        if (empty($directory)) {
            $directory = Form::input('Directory: ');

            if (empty($directory)) {
                Prints::print(value: "No directory specified.", exit: true, color: 'red');
            }
        }

        $path = getcwd() . '/' . $directory;

        if (is_dir($path) && ConsoleHelper::projectIsInitialized($path)) {
            Prints::print(value: "Project is already initialized.", exit: true, color: 'red');
        }

        File::mkdir([
            $path,
            $path . '/public',
            $path . '/public/assets',
            $path . '/App/Controller',
            $path . '/App/View',
            $path . '/App/Model',
            $path . '/storage',
            $path . '/storage/logs',
            $path . '/storage/cache'
        ]);

        file_put_contents($path . '/public/index.php', $this->indexTemplate($directory));
        file_put_contents($path . '/public/.htaccess', $this->htaccessTemplate());
        file_put_contents($path . '/.gitignore', $this->gitignoreTemplate());
        file_put_contents($path . '/.env.local', $this->envlocalTemplate());
        Kernel::$projectPath = $path;
        (new MakeController())->handle('index');
        Prints::print(value: "Project initialized in: {$path}", exit: true, color: 'green');
    }

    /**
     * @param string $path
     * @return string
     */
    private function indexTemplate(string $path): string
    {
        $vendorPath = '../';

        for ($x = 0; $x <= 10; $x++) {
            if (file_exists(getcwd() . '/public/' . $vendorPath . 'vendor/autoload.php')) {
                break;
            } else {
                $vendorPath .= '../';
            }
        }

        $vendorPath .= 'vendor/autoload.php';

        return <<<PHP
<?php

require('{$vendorPath}');

try {
    \$route = new \NimblePHP\\Framework\Routes\Route(new \NimblePHP\\Framework\Request());
    \$kernel = new \NimblePHP\\Framework\Kernel(\$route);
    \$kernel->handle();
} catch (\\Throwable \$throwable) {
    if (\$_ENV['DEBUG']) {
        throw \$throwable;
    } else {
        echo 'Error';
    }
}
PHP;
    }

    /**
     * @return string
     */
    private function htaccessTemplate(): string
    {
        return <<<HTACCESS
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.+)$ index.php?url=$1 [QSA,L]
HTACCESS;
    }

    /**
     * @return string
     */
    private function gitignoreTemplate(): string
    {
        return "storage/cache/*
storage/logs/*
storage/session/*
.env.local";
    }

    /**
     * @return string
     */
    private function envlocalTemplate(): string
    {
        return "DEBUG=true";
    }

}