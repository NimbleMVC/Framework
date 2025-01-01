<?php

namespace Nimblephp\framework;

/**
 * Storage class
 */
class Storage
{

    /**
     * Directory
     * @var string
     */
    private string $directory;

    /**
     * Init storage
     * @param string $directory
     */
    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    /**
     * Get directory
     * @return string
     */
    public function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * Is exists
     * @param string $fileName
     * @return bool
     */
    public function isExists(string $fileName): bool
    {
        return file_exists($this->getDirectory() . '/' . $fileName);
    }

    /**
     * Is exists directory
     * @return bool
     */
    public function isExistsDirectory(): bool
    {
        return is_dir($this->getDirectory());
    }

    /**
     * Write
     * @param string $fileName
     * @param string $data
     * @param bool $append
     * @return false|int
     */
    public function write(string $fileName, string $data, bool $append = false): false|int
    {
        $this->createDirectory();

        return file_put_contents(
            $this->getDirectory() . '/' . $fileName,
            $data,
            $append ? FILE_APPEND : 0
        );
    }

    /**
     * Read
     * @param string $fileName
     * @return false|string
     */
    public function read(string $fileName): false|string
    {
        if (!$this->isExists($fileName)) {
            return false;
        }

        return file_get_contents($this->getDirectory() . '/' . $fileName);
    }

    /**
     * Delete
     * @param string $fileName
     * @return bool
     */
    public function delete(string $fileName): bool
    {
        if (!$this->isExists($fileName)) {
            return false;
        }

        return unlink($this->getDirectory() . '/' . $fileName);
    }

    /**
     * Create directory
     * @return bool
     */
    private function createDirectory(): bool
    {
        if ($this->isExistsDirectory()) {
            return true;
        }

        return mkdir($this->getDirectory(), 0777, true);
    }

}