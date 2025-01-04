<?php

namespace Nimblephp\framework;

use Nimblephp\framework\Exception\NimbleException;

/**
 * Storage class
 */
class Storage
{

    /**
     * Storage base path
     * @var string
     */
    private string $basePath;

    /**
     * Init storage class
     * @throws NimbleException
     */
    public function __construct(string $directory, bool $securePath = true)
    {
        $this->basePath = rtrim(Kernel::$projectPath, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR
            . ($securePath ? trim($directory, DIRECTORY_SEPARATOR) : $directory);

        $this->createBasePath();
    }

    /**
     * Put file
     * @param string $filePath
     * @param string $content
     * @return true
     * @throws NimbleException
     */
    public function put(string $filePath, string $content): true
    {
        $fullPath = $this->getFullPath($filePath);
        $this->ensureDirectoryExists(dirname($fullPath));

        if (file_put_contents($fullPath, $content) === false) {
            throw new NimbleException(sprintf('Failed to write to file "%s"', $fullPath));
        }

        return true;
    }

    /**
     * Append to file
     * @param string $filePath
     * @param string $content
     * @param string $append
     * @return true
     * @throws NimbleException
     */
    public function append(string $filePath, string $content, string $append = PHP_EOL): true
    {
        $fullPath = $this->getFullPath($filePath);
        $this->ensureDirectoryExists(dirname($fullPath));

        if (file_put_contents($fullPath, $content . $append, FILE_APPEND) === false) {
            throw new NimbleException(sprintf('Failed to append to file "%s"', $fullPath));
        }

        return true;
    }

    /**
     * Get file content
     * @param string $filePath
     * @return string|null
     */
    public function get(string $filePath): ?string
    {
        if (file_exists($this->getFullPath($filePath))) {
            return file_get_contents($this->getFullPath($filePath));
        }

        return null;
    }

    /**
     * Delete file
     * @param string $filePath
     * @return bool
     */
    public function delete(string $filePath): bool
    {
        if (file_exists($this->getFullPath($filePath))) {
            return unlink($this->getFullPath($filePath));
        }

        return false;
    }

    /**
     * Delete file
     * @param string $filePath
     * @return bool
     */
    public function exists(string $filePath): bool
    {
        return file_exists($this->getFullPath($filePath));
    }

    /**
     * List files
     * @param bool $extend
     * @return array
     */
    public function listFiles(bool $extend = false): array
    {
        if (!is_dir($this->basePath)) {
            return [];
        }

        if (!$extend) {
            return array_diff(scandir($this->basePath), ['.', '..']);
        }

        $list = [];

        foreach (array_diff(scandir($this->basePath), ['.', '..']) as $name) {
            $list[$name] = $this->getMetadata($name);
        }

        return $list;
    }

    /**
     * Get full path
     * @param string $filePath
     * @return string
     */
    public function getFullPath(string $filePath): string
    {
        return $this->basePath
            . DIRECTORY_SEPARATOR
            . ltrim($filePath, DIRECTORY_SEPARATOR);
    }

    /**
     * Copy a file to a new location
     * @param string $sourcePath
     * @param string $destinationPath inside storage
     * @return bool
     */
    public function copy(string $sourcePath, string $destinationPath): bool
    {
        $sourceFullPath = $sourcePath;
        $destinationFullPath = $this->getFullPath($destinationPath);

        if (!file_exists($sourceFullPath)) {
            return false;
        }

        $directory = dirname($destinationFullPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return copy($sourceFullPath, $destinationFullPath);
    }

    /**
     * Move a file to a new location
     * @param string $sourcePath
     * @param string $destinationPath inside storage
     * @return bool
     */
    public function move(string $sourcePath, string $destinationPath): bool
    {
        $sourceFullPath = $sourcePath;
        $destinationFullPath = $this->getFullPath($destinationPath);

        if (!file_exists($sourceFullPath)) {
            return false;
        }

        $directory = dirname($destinationFullPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return rename($sourceFullPath, $destinationFullPath);
    }

    /**
     * Get file metadata
     * @param string $filePath
     * @return array|null
     */
    public function getMetadata(string $filePath): ?array
    {
        if (!file_exists($this->getFullPath($filePath))) {
            return null;
        }

        return [
            'size' => filesize($this->getFullPath($filePath)),
            'modified' => filemtime($this->getFullPath($filePath)),
            'type' => filetype($this->getFullPath($filePath)),
            'path' => $this->basePath . DIRECTORY_SEPARATOR . basename($filePath)
        ];
    }

    /**
     * Create base directory if not exists
     * @return void
     * @throws NimbleException
     */
    private function createBasePath(): void
    {
        if (!is_dir($this->basePath)) {
            if (!mkdir($this->basePath, 0755, true) && !is_dir($this->basePath)) {
                throw new NimbleException(sprintf('Directory "%s" was not created', $this->basePath));
            }
        }
    }

    /**
     * Ensure directory exists
     * @param string $directory
     * @return void
     * @throws NimbleException
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new NimbleException(sprintf('Directory "%s" was not created', $directory));
            }
        }
    }


}