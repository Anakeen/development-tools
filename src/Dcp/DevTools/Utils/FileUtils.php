<?php

namespace Dcp\DevTools\Utils;

class FileUtils
{
    static function recursiveRm($path, $includingSelf = false)
    {
        if (is_dir($path)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path,
                    \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $filename => $fileInfo) {
                if ($fileInfo->isDir()) {
                    rmdir($filename);
                } else {
                    unlink($filename);
                }
            }
            if ($includingSelf) {
                rmdir($path);
            }
        } else {
            unlink($path);
        }
    }

    /**
     * Recursively copy files from one directory to another
     *
     * @param String $src  - Source of files being moved
     * @param String $dest - Destination of files being moved
     *
     * @return bool
     */
    static function recursiveCp($src, $dest)
    {

        // If source is not a directory stop processing
        if (!is_dir($src)) {
            return false;
        }

        // If the destination directory does not exist create it
        if (!is_dir($dest)) {
            if (!mkdir($dest)) {
                // If the destination directory could not be created stop processing
                return false;
            }
        }

        // Open the source directory to read in files
        $i = new \DirectoryIterator($src);
        foreach ($i as $f) {
            if ($f->isFile()) {
                copy($f->getRealPath(), "$dest/" . $f->getFilename());
            } else {
                if (!$f->isDot() && $f->isDir()) {
                    self::recursiveCp($f->getRealPath(), "$dest/$f");
                }
            }
        }

        return true;
    }
}
