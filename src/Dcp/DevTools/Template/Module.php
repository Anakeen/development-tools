<?php

namespace Dcp\DevTools\Template;

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
        //$outputPath = $outputPath . DIRECTORY_SEPARATOR . $arguments['name'];
        $this->createDir($outputPath);
        if (isset($arguments["application"])) {
            $application = explode(",", $arguments["application"]);
            foreach ($application as $currentApplication) {
                if (isset($currentApplication) && !$this->checkLogicalName($currentApplication)) {
                    throw new Exception("You need to set the name of the application with a valid name $currentApplication : " . $this->logicalNameRegExp);
                }
                $this->createDir($outputPath . DIRECTORY_SEPARATOR . $currentApplication)
                    ->createDir($outputPath . DIRECTORY_SEPARATOR . $currentApplication . DIRECTORY_SEPARATOR . "Images")
                    ->createDir($outputPath . DIRECTORY_SEPARATOR . $currentApplication . DIRECTORY_SEPARATOR . "Layout");
            }
        }
        if (isset($arguments["external"])) {
            mkdir($outputPath . DIRECTORY_SEPARATOR . "EXTERNALS");
        }
        if (isset($arguments["style"])) {
            mkdir($outputPath . DIRECTORY_SEPARATOR . "STYLE");
        }
        if (isset($arguments["images"])) {
            mkdir($outputPath . DIRECTORY_SEPARATOR . "Images");
        }
        if (isset($arguments["api"])) {
            mkdir($outputPath . DIRECTORY_SEPARATOR . "API");
        }
        if (isset($arguments["rest"])) {
            mkdir($outputPath . DIRECTORY_SEPARATOR . "HTTPAPI_V1");
            mkdir($outputPath . DIRECTORY_SEPARATOR . "HTTPAPI_V1"
                    . DIRECTORY_SEPARATOR . "rules.d");
        }
        mkdir($outputPath . DIRECTORY_SEPARATOR . "locale");
        if (isset($arguments["lang"]) && is_array($arguments["lang"])) {
            foreach ($arguments["lang"] as $currentLang) {
                mkdir($outputPath . DIRECTORY_SEPARATOR . "locale" . DIRECTORY_SEPARATOR . $currentLang);
            }
        }
    }

    public function createDir($path)
    {
        if (!is_dir($path)) {
            mkdir($path);
            //throw new Exception("The dir $path already exist.");
        }
        return $this;
    }
} 