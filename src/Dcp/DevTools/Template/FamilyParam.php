<?php

namespace Dcp\DevTools\Template;

class FamilyParam extends Template
{
    public function render($arguments, $outputPath, $force = false)
    {
        if (!empty($outputPath) && !is_dir($outputPath)) {
            throw new Exception("The output path $outputPath is not a dir");
        }
        if (!isset($arguments["name"]) || !$this->checkLogicalName($arguments["name"])) {
            throw new Exception("You need to set the name of the family with a valid name " . $this->logicalNameRegExp);
        }
        if (!isset($arguments["dfldid"])) {
            $arguments["dfldid"] = "DIR_" . $arguments["name"];
        }
        if (!isset($arguments["title"])) {
            $arguments["title"] = $arguments["name"];
        }

        if (!isset($arguments["icon"])) {
            $arguments["icon"] = strtolower($arguments["name"]) . ".png";
        }

        if (!empty($outputPath)) {
            $outputPath .= DIRECTORY_SEPARATOR . $arguments["name"] . "__PARAM.csv";
        }
        return parent::main_render("family_param", $arguments, $outputPath, $force);
    }
} 