<?php

namespace NimblePHP\Framework\CLI\Commands;

use Composer\InstalledVersions;
use Krzysztofzylka\Console\Form;
use Krzysztofzylka\File\File;
use krzysztofzylka\DatabaseManager\DatabaseConnect;
use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use NimblePHP\Framework\Cache as FrameworkCache;
use NimblePHP\Framework\CLI\Attributes\ConsoleCommand;
use NimblePHP\Framework\CLI\ConsoleHelper;
use NimblePHP\Framework\CLI\Output;
use NimblePHP\Framework\Config;
use NimblePHP\Framework\DataStore;
use NimblePHP\Framework\Exception\DatabaseException;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Module\Interfaces\ModuleInterface;
use NimblePHP\Framework\Module\Interfaces\ModuleUpdateInterface;
use NimblePHP\Framework\Module\ModuleRegister;
use NimblePHP\Framework\Routes\Route;
use Throwable;


class Project
{

    private const DOCTOR_STATUS_OK = 'ok';
    private const DOCTOR_STATUS_WARN = 'warn';
    private const DOCTOR_STATUS_ERROR = 'error';

    #[ConsoleCommand(
        'project:info',
        'Show project information',
        help: 'Display basic information about the current Nimble project and environment.',
        usage: 'php vendor/bin/nimble project:info',
        examples: [
            ['command' => 'php vendor/bin/nimble project:info', 'description' => 'Show the current project path, package and environment details.'],
        ]
    )]
    public function projectInfo(Output $output): int
    {
        $composer = $this->readComposerJson();
        $modules = new ModuleRegister();
        $modules->autoRegister();

        $output->section('Project info', Kernel::$projectPath);
        $output->kv([
            'Project path' => Kernel::$projectPath,
            'Composer package' => (string)($composer['name'] ?? '-'),
            'Project initialized' => ConsoleHelper::projectIsInitialized(Kernel::$projectPath) ? 'yes' : 'no',
            'Framework version' => $this->resolveInstalledPackageVersion('nimblephp/framework'),
            'PHP version' => PHP_VERSION,
            'Modules discovered' => (string)count(ModuleRegister::getAll()),
            '.env' => file_exists(Kernel::$projectPath . '/.env') ? 'present' : 'missing',
            '.env.local' => file_exists(Kernel::$projectPath . '/.env.local') ? 'present' : 'missing',
            'storage/logs' => is_dir(Kernel::$projectPath . '/storage/logs') ? 'present' : 'missing',
            'storage/cache' => is_dir(Kernel::$projectPath . '/storage/cache') ? 'present' : 'missing',
        ]);

        return 0;
    }

    #[ConsoleCommand(
        'project:doctor',
        'Run project health checks',
        help: 'Run a practical health check for the current project, including structure, env, storage, PHP runtime, modules and database configuration.',
        usage: 'php vendor/bin/nimble project:doctor',
        examples: [
            ['command' => 'php vendor/bin/nimble project:doctor', 'description' => 'Run a health check for the current project.'],
        ]
    )]
    public function projectDoctor(Output $output): int
    {
        $checks = [];

        $this->addDoctorCheck(
            $checks,
            is_dir(Kernel::$projectPath . '/App') ? self::DOCTOR_STATUS_OK : self::DOCTOR_STATUS_ERROR,
            'App directory',
            Kernel::$projectPath . '/App'
        );
        $this->addDoctorCheck(
            $checks,
            is_dir(Kernel::$projectPath . '/public') ? self::DOCTOR_STATUS_OK : self::DOCTOR_STATUS_ERROR,
            'public directory',
            Kernel::$projectPath . '/public'
        );
        $this->addDoctorCheck(
            $checks,
            is_dir(Kernel::$projectPath . '/storage') ? self::DOCTOR_STATUS_OK : self::DOCTOR_STATUS_ERROR,
            'storage directory',
            Kernel::$projectPath . '/storage'
        );
        $this->addDoctorCheck(
            $checks,
            file_exists(Kernel::$projectPath . '/composer.json') ? self::DOCTOR_STATUS_OK : self::DOCTOR_STATUS_ERROR,
            'composer.json',
            file_exists(Kernel::$projectPath . '/composer.json') ? 'present' : 'missing'
        );
        $this->addDoctorCheck(
            $checks,
            file_exists(Kernel::$projectPath . '/vendor/autoload.php') ? self::DOCTOR_STATUS_OK : self::DOCTOR_STATUS_ERROR,
            'vendor autoload',
            file_exists(Kernel::$projectPath . '/vendor/autoload.php') ? 'present' : 'missing',
            'Run composer install.'
        );

        try {
            ConsoleHelper::loadConfig();
            $this->addDoctorCheck($checks, self::DOCTOR_STATUS_OK, 'Environment files', 'configuration loaded');
        } catch (Throwable $throwable) {
            $this->addDoctorCheck($checks, self::DOCTOR_STATUS_ERROR, 'Environment files', $throwable->getMessage());
        }

        $hasEnv = file_exists(Kernel::$projectPath . '/.env');
        $hasEnvLocal = file_exists(Kernel::$projectPath . '/.env.local');
        $this->addDoctorCheck(
            $checks,
            ($hasEnv || $hasEnvLocal) ? self::DOCTOR_STATUS_OK : self::DOCTOR_STATUS_WARN,
            'Project env files',
            'env=' . ($hasEnv ? 'yes' : 'no') . ', env.local=' . ($hasEnvLocal ? 'yes' : 'no'),
            'Create .env or .env.local with project configuration.'
        );
        $debugEnabled = filter_var(Config::get('DEBUG', false), FILTER_VALIDATE_BOOL);
        $this->addDoctorCheck(
            $checks,
            $debugEnabled ? self::DOCTOR_STATUS_WARN : self::DOCTOR_STATUS_OK,
            'Debug mode',
            $debugEnabled ? 'DEBUG=true' : 'DEBUG=false',
            'Disable DEBUG outside local development.'
        );

        foreach ($this->runRuntimeDoctorChecks() as $check) {
            $checks[] = $check;
        }

        foreach ($this->runEnvQualityDoctorChecks() as $check) {
            $checks[] = $check;
        }

        $sessionDriver = trim((string)Config::get('SESSION_DRIVER', 'none'));
        $this->addWritableDoctorCheck($checks, 'storage/cache', Kernel::$projectPath . '/storage/cache');
        $this->addWritableDoctorCheck($checks, 'storage/logs', Kernel::$projectPath . '/storage/logs');
        if ($sessionDriver === 'file') {
            $this->addWritableDoctorCheck($checks, 'storage/session', Kernel::$projectPath . '/storage/session', self::DOCTOR_STATUS_WARN);
        }

        foreach ($this->runPermissionDoctorChecks() as $check) {
            $checks[] = $check;
        }

        $this->addDoctorCheck($checks, self::DOCTOR_STATUS_OK, 'PHP version', PHP_VERSION);
        $this->addExtensionDoctorCheck($checks, 'json');
        $this->addExtensionDoctorCheck($checks, 'mbstring');
        $this->addExtensionDoctorCheck($checks, 'openssl');
        $this->addDoctorCheck(
            $checks,
            extension_loaded('xdebug') ? self::DOCTOR_STATUS_WARN : self::DOCTOR_STATUS_OK,
            'Xdebug in CLI',
            extension_loaded('xdebug') ? 'enabled' : 'disabled'
        );

        $modules = new ModuleRegister();

        try {
            $modules->autoRegister();
            $moduleCount = count(ModuleRegister::getAll());
            $this->addDoctorCheck(
                $checks,
                $moduleCount > 0 ? self::DOCTOR_STATUS_OK : self::DOCTOR_STATUS_WARN,
                'Modules discovered',
                (string)$moduleCount
            );
        } catch (Throwable $throwable) {
            $this->addDoctorCheck($checks, self::DOCTOR_STATUS_ERROR, 'Modules discovered', $throwable->getMessage());
        }

        foreach ($this->runSessionDoctorChecks() as $check) {
            $checks[] = $check;
        }

        foreach ($this->runLogsDoctorChecks() as $check) {
            $checks[] = $check;
        }

        foreach ($this->runCacheDoctorChecks() as $check) {
            $checks[] = $check;
        }

        foreach ($this->runDatabaseDoctorChecks() as $check) {
            $checks[] = $check;
        }

        $output->section('Project doctor', Kernel::$projectPath);

        foreach ($checks as $check) {
            $this->renderDoctorCheck($output, $check);
        }

        $summary = $this->summarizeDoctorChecks($checks);
        $output->section('Summary');
        $output->kv([
            'Checks' => (string)$summary['checks'],
            'OK' => (string)$summary['ok'],
            'Warnings' => (string)$summary['warnings'],
            'Errors' => (string)$summary['errors'],
        ]);

        if ($summary['recommendations'] !== []) {
            $output->section('Recommended actions');
            $output->bulletList($summary['recommendations']);
        }

        return $summary['errors'] > 0 ? 1 : 0;
    }

    #[ConsoleCommand(
        'project:structure',
        'Project structure',
        help: 'Display the current project directory tree.',
        usage: 'php vendor/bin/nimble project:structure',
        examples: [
            ['command' => 'php vendor/bin/nimble project:structure', 'description' => 'Print the current project structure.'],
        ]
    )]
    public function projectStructure(Output $output, string $directory = ''): int
    {
        $status = 0;
        passthru('tree -L 2 ' . getcwd(), $status);

        return $status;
    }

    #[ConsoleCommand(
        'project:size',
        'Project size',
        help: 'Display the disk usage of the current project directory.',
        usage: 'php vendor/bin/nimble project:size',
        examples: [
            ['command' => 'php vendor/bin/nimble project:size', 'description' => 'Show the total size of the project.'],
        ]
    )]
    public function projectSize(): int
    {
        $status = 0;
        passthru('du -sh .', $status);

        return $status;
    }

    #[ConsoleCommand(
        'project:init',
        'Project initialization',
        help: 'Initialize a new Nimble project directory with the default structure.',
        usage: 'php vendor/bin/nimble project:init [directory]',
        arguments: [
            ['name' => 'directory', 'description' => 'Target directory name for the new project.', 'default' => 'prompt'],
        ],
        examples: [
            ['command' => 'php vendor/bin/nimble project:init demo', 'description' => 'Create a new project in the demo directory.'],
        ]
    )]
    public function projectInit(Output $output, string $directory = ''): int
    {
        if (empty($directory)) {
            $directory = Form::input('Directory: ');

            if (empty($directory)) {
                $output->error('No directory specified.');

                return 1;
            }
        }

        $path = getcwd() . '/' . $directory;

        if (is_dir($path) && ConsoleHelper::projectIsInitialized($path)) {
            $output->error('Project is already initialized.');

            return 1;
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
        $makeController = new MakeController();
        $makeController->run(
            new \NimblePHP\Framework\CLI\Input(
                'make:controller',
                ['index', '--route=/'],
                [0 => 'index', 'route' => '/'],
                [['name' => 'name']]
            ),
            $output
        );
        $output->success("Project initialized in: {$path}");

        return 0;
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


    /**
     * @return void
     * @throws DatabaseException
     * @throws Throwable
     */
    #[ConsoleCommand(
        'project:update',
        'Project update',
        help: 'Clear cache and run module update hooks for the current project.',
        usage: 'php vendor/bin/nimble project:update',
        examples: [
            ['command' => 'php vendor/bin/nimble project:update', 'description' => 'Refresh cache and run module update hooks.'],
        ]
    )]
    public function update(Output $output): int
    {
        ConsoleHelper::loadConfig();
        ConsoleHelper::initKernel();

        $cache = new Cache();
        $cache->cacheClear(['no-exit' => true], $output);

        $modules = new ModuleRegister();

        foreach ($modules->getAll() as $module) {
            /** @var DataStore $classes */
            $classes = $module['classes'];

            if ($classes->exists('module')) {
                /** @var ModuleInterface $classes */
                $moduleClass = $classes->get('module');

                if ($moduleClass instanceof ModuleUpdateInterface) {
                    $output->success('Run update module: ' . $module['name']);
                    $moduleClass->onUpdate();
                }
            }
        }

        $output->success('Project updated');

        return 0;
    }

    /**
     * @return array
     */
    private function readComposerJson(): array
    {
        $composerPath = Kernel::$projectPath . '/composer.json';

        if (!file_exists($composerPath)) {
            return [];
        }

        $content = file_get_contents($composerPath);

        if ($content === false) {
            return [];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param string $package
     * @return string
     */
    private function resolveInstalledPackageVersion(string $package): string
    {
        if (!InstalledVersions::isInstalled($package)) {
            return '-';
        }

        return InstalledVersions::getPrettyVersion($package) ?? '-';
    }

    /**
     * @param array $checks
     * @param string $status
     * @param string $label
     * @param string $details
     * @param string|null $recommendation
     * @return void
     */
    private function addDoctorCheck(
        array &$checks,
        string $status,
        string $label,
        string $details,
        ?string $recommendation = null
    ): void
    {
        $checks[] = [
            'status' => $status,
            'label' => $label,
            'details' => $details,
            'recommendation' => $recommendation,
        ];
    }

    /**
     * @param array $checks
     * @param string $label
     * @param string $path
     * @param string $missingStatus
     * @return void
     */
    private function addWritableDoctorCheck(
        array &$checks,
        string $label,
        string $path,
        string $missingStatus = self::DOCTOR_STATUS_ERROR
    ): void {
        if (!file_exists($path)) {
            $this->addDoctorCheck($checks, $missingStatus, $label, 'missing');

            return;
        }

        $this->addDoctorCheck(
            $checks,
            is_writable($path) ? self::DOCTOR_STATUS_OK : self::DOCTOR_STATUS_ERROR,
            $label,
            is_writable($path) ? 'writable' : 'not writable',
            'Fix write permissions for ' . $label . '.'
        );
    }

    /**
     * @param array $checks
     * @param string $extension
     * @return void
     */
    private function addExtensionDoctorCheck(array &$checks, string $extension): void
    {
        $this->addDoctorCheck(
            $checks,
            extension_loaded($extension) ? self::DOCTOR_STATUS_OK : self::DOCTOR_STATUS_ERROR,
            'PHP extension: ' . $extension,
            extension_loaded($extension) ? 'loaded' : 'missing',
            'Install and enable the ' . $extension . ' PHP extension.'
        );
    }

    /**
     * @return array
     */
    private function runRuntimeDoctorChecks(): array
    {
        $checks = [];
        $sessionDriver = trim((string)Config::get('SESSION_DRIVER', 'none'));

        $this->addDoctorCheck(
            $checks,
            is_dir(Kernel::$projectPath . '/public/assets') ? self::DOCTOR_STATUS_OK : self::DOCTOR_STATUS_WARN,
            'Runtime dir: public/assets',
            is_dir(Kernel::$projectPath . '/public/assets') ? 'present' : 'missing',
            'Create public/assets for generated runtime assets.'
        );
        if ($sessionDriver === 'file') {
            $this->addDoctorCheck(
                $checks,
                is_dir(Kernel::$projectPath . '/storage/session') ? self::DOCTOR_STATUS_OK : self::DOCTOR_STATUS_WARN,
                'Runtime dir: storage/session',
                is_dir(Kernel::$projectPath . '/storage/session') ? 'present' : 'missing',
                'Create storage/session or let the file session driver create it on first run.'
            );
        }

        return $checks;
    }

    /**
     * @return array
     */
    private function runEnvQualityDoctorChecks(): array
    {
        $checks = [];

        foreach (['DEFAULT_CONTROLLER', 'DEFAULT_METHOD'] as $key) {
            $value = trim((string)Config::get($key, ''));
            $this->addDoctorCheck(
                $checks,
                $value !== '' ? self::DOCTOR_STATUS_OK : self::DOCTOR_STATUS_WARN,
                'Env quality: ' . $key,
                $value !== '' ? $value : 'missing',
                'Set ' . $key . ' in the environment configuration.'
            );
        }

        $placeholderKeys = ['APP_KEY', 'DATABASE_PASSWORD', 'SESSION_REDIS_PASSWORD'];

        foreach ($placeholderKeys as $key) {
            $value = trim((string)Config::get($key, ''));

            if ($value === '') {
                continue;
            }

            if ($this->looksLikePlaceholderValue($value)) {
                $this->addDoctorCheck(
                    $checks,
                    self::DOCTOR_STATUS_WARN,
                    'Env quality: ' . $key,
                    'placeholder-like value detected',
                    'Replace the placeholder value for ' . $key . '.'
                );
            }
        }

        return $checks;
    }

    /**
     * @return array
     */
    private function runPermissionDoctorChecks(): array
    {
        $checks = [];

        foreach ([
            'App' => Kernel::$projectPath . '/App',
            'public' => Kernel::$projectPath . '/public',
            'storage' => Kernel::$projectPath . '/storage',
        ] as $label => $path) {
            $this->addDoctorCheck(
                $checks,
                is_readable($path) ? self::DOCTOR_STATUS_OK : self::DOCTOR_STATUS_ERROR,
                'Permission: ' . $label,
                is_readable($path) ? 'readable' : 'not readable',
                'Fix read permissions for ' . $path . '.'
            );
        }

        return $checks;
    }

    /**
     * @return array
     */
    private function runSessionDoctorChecks(): array
    {
        $checks = [];
        $driver = trim((string)Config::get('SESSION_DRIVER', 'none'));

        $this->addDoctorCheck($checks, self::DOCTOR_STATUS_OK, 'Session driver', $driver);

        switch ($driver) {
            case 'none':
                $this->addDoctorCheck(
                    $checks,
                    self::DOCTOR_STATUS_WARN,
                    'Session backend',
                    'disabled',
                    'Enable a session driver if the application needs sessions.'
                );
                break;
            case 'file':
                $sessionPath = Kernel::$projectPath . '/storage/session';
                $this->addDoctorCheck(
                    $checks,
                    is_dir($sessionPath) ? self::DOCTOR_STATUS_OK : self::DOCTOR_STATUS_WARN,
                    'Session path',
                    is_dir($sessionPath) ? $sessionPath : 'missing',
                    'Create storage/session for file-based sessions.'
                );
                if (is_dir($sessionPath)) {
                    $this->addDoctorCheck(
                        $checks,
                        is_writable($sessionPath) ? self::DOCTOR_STATUS_OK : self::DOCTOR_STATUS_ERROR,
                        'Session path writable',
                        is_writable($sessionPath) ? 'yes' : 'no',
                        'Fix permissions for storage/session.'
                    );
                }
                break;
            case 'redis':
                $redisHost = trim((string)Config::get('SESSION_REDIS_HOST', ''));
                $this->addDoctorCheck(
                    $checks,
                    extension_loaded('redis') ? self::DOCTOR_STATUS_OK : self::DOCTOR_STATUS_WARN,
                    'Redis extension',
                    extension_loaded('redis') ? 'loaded' : 'missing',
                    'Install the redis PHP extension for Redis session handling.'
                );
                $this->addDoctorCheck(
                    $checks,
                    $redisHost !== '' ? self::DOCTOR_STATUS_OK : self::DOCTOR_STATUS_ERROR,
                    'Session redis host',
                    $redisHost !== '' ? $redisHost : 'missing',
                    'Set SESSION_REDIS_HOST for Redis sessions.'
                );
                break;
            default:
                $this->addDoctorCheck(
                    $checks,
                    self::DOCTOR_STATUS_ERROR,
                    'Session backend',
                    'unsupported driver: ' . $driver,
                    'Use a supported session driver such as file or redis.'
                );
                break;
        }

        return $checks;
    }

    /**
     * @return array
     */
    private function runLogsDoctorChecks(): array
    {
        $checks = [];
        $logPath = Kernel::$projectPath . '/storage/logs';

        if (!is_dir($logPath)) {
            $this->addDoctorCheck(
                $checks,
                self::DOCTOR_STATUS_WARN,
                'Log files',
                'storage/logs missing',
                'Create storage/logs for application logs.'
            );

            return $checks;
        }

        $logFiles = glob($logPath . '/*.log.json') ?: [];
        $logCount = count($logFiles);
        $logSize = 0;

        foreach ($logFiles as $logFile) {
            $logSize += (int)(filesize($logFile) ?: 0);
        }

        $this->addDoctorCheck($checks, self::DOCTOR_STATUS_OK, 'Log files count', (string)$logCount);
        $this->addDoctorCheck(
            $checks,
            $logSize > 10 * 1024 * 1024 ? self::DOCTOR_STATUS_WARN : self::DOCTOR_STATUS_OK,
            'Log files size',
            $this->formatBytes($logSize),
            'Review or prune large log files in storage/logs.'
        );

        return $checks;
    }

    /**
     * @return array
     */
    private function runCacheDoctorChecks(): array
    {
        $checks = [];
        $cacheEnabled = filter_var(Config::get('CACHE_ROUTE', false), FILTER_VALIDATE_BOOL);
        $cacheStoragePath = Kernel::$projectPath . '/storage/cache';
        $cacheFiles = is_dir($cacheStoragePath) ? glob($cacheStoragePath . '/*.cache') ?: [] : [];
        $routeCache = new FrameworkCache();

        $this->addDoctorCheck(
            $checks,
            $cacheEnabled ? self::DOCTOR_STATUS_OK : self::DOCTOR_STATUS_WARN,
            'Route cache enabled',
            $cacheEnabled ? 'yes' : 'no',
            'Enable CACHE_ROUTE and run routes:generate for faster route bootstrapping.'
        );
        $this->addDoctorCheck(
            $checks,
            $routeCache->has(Route::$cacheKey) ? self::DOCTOR_STATUS_OK : self::DOCTOR_STATUS_WARN,
            'Route cache entry',
            $routeCache->has(Route::$cacheKey) ? 'present' : 'missing',
            'Run php vendor/bin/nimble routes:generate to rebuild cached routes.'
        );
        $this->addDoctorCheck(
            $checks,
            count($cacheFiles) > 100 ? self::DOCTOR_STATUS_WARN : self::DOCTOR_STATUS_OK,
            'Cache files',
            (string)count($cacheFiles),
            'Clear or review cache files in storage/cache.'
        );

        return $checks;
    }

    /**
     * @return array
     */
    private function runDatabaseDoctorChecks(): array
    {
        $checks = [];

        if (!Config::get('DATABASE', false)) {
            $this->addDoctorCheck($checks, self::DOCTOR_STATUS_WARN, 'Database', 'disabled');

            return $checks;
        }

        $databaseType = trim((string)Config::get('DATABASE_TYPE', ''));

        if ($databaseType === '') {
            $this->addDoctorCheck($checks, self::DOCTOR_STATUS_ERROR, 'Database type', 'missing');

            return $checks;
        }

        $this->addDoctorCheck($checks, self::DOCTOR_STATUS_OK, 'Database type', $databaseType);

        if ($databaseType === 'mysql') {
            $required = ['DATABASE_HOST', 'DATABASE_NAME', 'DATABASE_USERNAME', 'DATABASE_PORT', 'DATABASE_CHARSET'];

            foreach ($required as $key) {
                $value = trim((string)Config::get($key, ''));
                $this->addDoctorCheck(
                    $checks,
                    $value !== '' ? self::DOCTOR_STATUS_OK : self::DOCTOR_STATUS_ERROR,
                    'Database config: ' . $key,
                    $value !== '' ? 'present' : 'missing',
                    'Set ' . $key . ' for the database connection.'
                );
            }

            $password = (string)Config::get('DATABASE_PASSWORD', '');
            $this->addDoctorCheck(
                $checks,
                $password !== '' ? self::DOCTOR_STATUS_OK : self::DOCTOR_STATUS_WARN,
                'Database config: DATABASE_PASSWORD',
                $password !== '' ? 'present' : 'empty',
                'Set DATABASE_PASSWORD for the database connection.'
            );

            $port = (int)Config::get('DATABASE_PORT', 0);
            $this->addDoctorCheck(
                $checks,
                $port > 0 && $port <= 65535 ? self::DOCTOR_STATUS_OK : self::DOCTOR_STATUS_ERROR,
                'Database port',
                $port > 0 ? (string)$port : 'invalid',
                'Use a valid DATABASE_PORT value.'
            );

            $this->addExtensionDoctorCheck($checks, 'pdo_mysql');
        } elseif ($databaseType === 'sqlite') {
            $databasePath = trim((string)Config::get('DATABASE_PATH', ''));
            $fullDatabasePath = Kernel::$projectPath . DIRECTORY_SEPARATOR . $databasePath;
            $this->addDoctorCheck(
                $checks,
                $databasePath !== '' ? self::DOCTOR_STATUS_OK : self::DOCTOR_STATUS_ERROR,
                'Database config: DATABASE_PATH',
                $databasePath !== '' ? $databasePath : 'missing',
                'Set DATABASE_PATH for the sqlite database.'
            );
            if ($databasePath !== '') {
                $this->addDoctorCheck(
                    $checks,
                    file_exists($fullDatabasePath) ? self::DOCTOR_STATUS_OK : self::DOCTOR_STATUS_WARN,
                    'SQLite database file',
                    file_exists($fullDatabasePath) ? 'present' : 'missing',
                    'Create the sqlite database file or run migrations.'
                );
                $this->addDoctorCheck(
                    $checks,
                    is_writable(dirname($fullDatabasePath)) ? self::DOCTOR_STATUS_OK : self::DOCTOR_STATUS_ERROR,
                    'SQLite directory writable',
                    is_writable(dirname($fullDatabasePath)) ? 'yes' : 'no',
                    'Fix permissions for the sqlite database directory.'
                );
            }
            $this->addExtensionDoctorCheck($checks, 'pdo_sqlite');
        } else {
            $this->addDoctorCheck($checks, self::DOCTOR_STATUS_ERROR, 'Database type', 'unsupported: ' . $databaseType);

            return $checks;
        }

        try {
            $connect = DatabaseConnect::create();

            switch ($databaseType) {
                case 'mysql':
                    $connect->setType(DatabaseType::mysql);
                    $connect->setHost(trim((string)Config::get('DATABASE_HOST', '')));
                    $connect->setDatabaseName(trim((string)Config::get('DATABASE_NAME', '')));
                    $connect->setUsername(trim((string)Config::get('DATABASE_USERNAME', '')));
                    $connect->setPassword(trim((string)Config::get('DATABASE_PASSWORD', '')));
                    $connect->setPort((int)Config::get('DATABASE_PORT', 3306));
                    break;
                case 'sqlite':
                    $connect->setType(DatabaseType::sqlite);
                    $connect->setSqlitePath(Kernel::$projectPath . DIRECTORY_SEPARATOR . trim((string)Config::get('DATABASE_PATH', '')));
                    break;
            }

            $connect->connect(false);
            $this->addDoctorCheck($checks, self::DOCTOR_STATUS_OK, 'Database connection', 'successful');
        } catch (Throwable $throwable) {
            $this->addDoctorCheck($checks, self::DOCTOR_STATUS_ERROR, 'Database connection', $throwable->getMessage());
        }

        return $checks;
    }

    /**
     * @param Output $output
     * @param array $check
     * @return void
     */
    private function renderDoctorCheck(Output $output, array $check): void
    {
        $line = sprintf('[%s] %s - %s', strtoupper($check['status']), $check['label'], $check['details']);

        switch ($check['status']) {
            case self::DOCTOR_STATUS_OK:
                $output->success($line);
                break;
            case self::DOCTOR_STATUS_WARN:
                $output->warning($line);
                break;
            default:
                $output->error($line);
                break;
        }
    }

    /**
     * @param array $checks
     * @return array
     */
    private function summarizeDoctorChecks(array $checks): array
    {
        $summary = [
            'checks' => count($checks),
            'ok' => 0,
            'warnings' => 0,
            'errors' => 0,
            'recommendations' => [],
        ];

        foreach ($checks as $check) {
            switch ($check['status']) {
                case self::DOCTOR_STATUS_OK:
                    $summary['ok']++;
                    break;
                case self::DOCTOR_STATUS_WARN:
                    $summary['warnings']++;
                    break;
                default:
                    $summary['errors']++;
                    break;
            }

            if (
                ($check['status'] ?? self::DOCTOR_STATUS_OK) !== self::DOCTOR_STATUS_OK
                && isset($check['recommendation'])
                && is_string($check['recommendation'])
                && $check['recommendation'] !== ''
                && !in_array($check['recommendation'], $summary['recommendations'], true)
            ) {
                $summary['recommendations'][] = $check['recommendation'];
            }
        }

        return $summary;
    }

    /**
     * @param string $value
     * @return bool
     */
    private function looksLikePlaceholderValue(string $value): bool
    {
        $normalized = strtolower(trim($value));

        foreach (['changeme', 'change-me', 'example', 'your-', 'your_', 'todo'] as $fragment) {
            if (str_contains($normalized, $fragment)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return round($bytes / (1024 * 1024), 2) . ' MB';
    }

}
