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

    /**
     * Find the fully qualified class name.
     * @param string $className The base class name to search for.
     * @param string $subClass The namespace or subclass prefix to prepend when searching.
     * @return string|null Returns the full class name if found, or null if the class does not exist.
     */
    public static function findClassName(string $className, string $subClass): ?string
    {
        if (class_exists($className)) {
            return $className;
        } elseif (class_exists($subClass . $className)) {
            return $subClass . $className;
        }

        return null;
    }

}