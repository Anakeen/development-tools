<?php
/**
 * Created by PhpStorm.
 * User: charles
 * Date: 09/09/14
 * Time: 14:06
 */

namespace dcp\DevTools\Template;


class Module extends Template
{

    public function render($arguments, $outputPath, $force = false)
    {
        if (!empty($outputPath) && !is_dir($outputPath)) {
            throw new Exception("The output path $outputPath is not a dir");
        }
        if (!isset($arguments["name"]) || !$this->checkLogicalName($arguments["name"])) {
            throw new Exception("You need to set the name of the module with a valid name " . $this->logicalNameRegExp);
        }
        $outputPath = $outputPath . DIRECTORY_SEPARATOR . $arguments['name'];
        $this->createDir($outputPath);
        if (isset($arguments["application"]) && !$this->checkLogicalName($arguments["application"])) {
            throw new Exception("You need to set the name of the application with a valid name " . $this->logicalNameRegExp);
        }
        if (isset($arguments["application"])) {
            $this->createDir($outputPath . DIRECTORY_SEPARATOR . $arguments["application"])
                ->createDir($outputPath . DIRECTORY_SEPARATOR . $arguments["application"] . DIRECTORY_SEPARATOR . "Familles")
                ->createDir($outputPath . DIRECTORY_SEPARATOR . $arguments["application"] . DIRECTORY_SEPARATOR . "Images")
                ->createDir($outputPath . DIRECTORY_SEPARATOR . $arguments["application"] . DIRECTORY_SEPARATOR . "Layout");
        }
        if (isset($arguments["external"])) {
            mkdir($outputPath . DIRECTORY_SEPARATOR . "EXTERNALS");
        }
        if (isset($arguments["po"])) {
            mkdir($outputPath . DIRECTORY_SEPARATOR . "locale");
        }
        if (isset($arguments["style"])) {
            mkdir($outputPath . DIRECTORY_SEPARATOR . "STYLE");
        }
    }

    public function createDir($path)
    {
        if (is_dir($path)) {
            throw new Exception("The dir $path already exist.");
        }
        mkdir($path);
        return $this;
    }
} 