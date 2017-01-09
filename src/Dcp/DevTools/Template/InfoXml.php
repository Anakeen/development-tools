<?php

namespace Dcp\DevTools\Template;

class InfoXml extends Template {

    public function render($arguments, $outputPath, $force = false)
    {
        if (!empty($outputPath) && !is_dir($outputPath)) {
            throw new Exception("The output path $outputPath is not a dir");
        }
        if (!isset($arguments["name"]) || !$this->checkModuleName($arguments["name"])) {
            throw new Exception("You need to set the name of the module with a valid name " . $this->moduleNameRegExp);
        }
        if (isset($arguments["application"])) {
            $application = explode(",", $arguments["application"]);
            foreach ($application as $currentApplication) {
                if (!$this->checkLogicalName($currentApplication)) {
                    throw new Exception("You need to set the name of the application with a valid name $currentApplication : " . $this->logicalNameRegExp);
                }
            }
            $arguments["list_application"] = $application;
        }
        if (!isset($arguments["description"])) {
            $arguments["description"] = $arguments["name"];
        }
        if (!empty($outputPath)) {
            $outputPath .= DIRECTORY_SEPARATOR . "info.xml";
        }
        return parent::main_render("info", $arguments, $outputPath, $force);
    }
} 