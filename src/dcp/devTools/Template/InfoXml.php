<?php
/**
 * Created by PhpStorm.
 * User: charles
 * Date: 09/09/14
 * Time: 12:14
 */

namespace dcp\DevTools\Template;


class InfoXml extends Template {

    public function render($arguments, $outputPath, $force = false)
    {
        if (!empty($outputPath) && !is_dir($outputPath)) {
            throw new Exception("The output path $outputPath is not a dir");
        }
        if (!isset($arguments["name"]) || !$this->checkLogicalName($arguments["name"])) {
            throw new Exception("You need to set the name of the module with a valid name " . $this->logicalNameRegExp);
        }
        if (isset($arguments["application"]) && !$this->checkLogicalName($arguments["application"])) {
            throw new Exception("You need to set the name of the application with a valid name " . $this->logicalNameRegExp);
        }
        if (!isset($arguments["description"])) {
            $arguments["description"] = $arguments["name"];
        }
        if (!empty($outputPath)) {
            $outputPath .= DIRECTORY_SEPARATOR . "info.xml";
        }
        return parent::render("info", $arguments, $outputPath, $force);
    }
} 