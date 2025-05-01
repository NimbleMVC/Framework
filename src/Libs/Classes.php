<?php

namespace NimblePHP\Framework\Libs;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class Classes
{

    /**
     * Get all classes
     * @param string $directory
     * @param string $namespace
     * @return array
     */
    public static function getAllClasses(string $directory, string $namespace): array
    {
        $class = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = str_replace($directory, '', $file->getPathname());
                $className = $namespace . '\\' . trim(str_replace(['/', '\\'], '\\', $relativePath), '\\');
                $className = preg_replace('/\.php$/', '', $className);
                $class[] = $className;
            }
        }

        return $class;
    }

}