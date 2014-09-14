<?php
namespace Dcp\DevTools\Template;

class Action extends Template
{

    public function render($arguments, $outputPath, $force = false)
    {
        if (!empty($outputPath) && !is_dir($outputPath)) {
            throw new Exception("The output path $outputPath is not a dir");
        }
        if (!isset($arguments["name"]) || !$this->checkLogicalName($arguments["name"])) {
            throw new Exception("You need to set the name of the action with a valid name ".$this->logicalNameRegExp);
        }
        if (isset($arguments["layout"])) {
            $layoutPath = $outputPath . DIRECTORY_SEPARATOR . "Layout". DIRECTORY_SEPARATOR;
            if (!is_dir($layoutPath)) {
                mkdir($layoutPath);
            }
            $arguments["layoutFileName"] = strtolower($arguments["name"]) . ".html";
            $layoutPath .= $arguments["layoutFileName"];
            parent::main_render("action_layout", $arguments, $layoutPath, $force);
        }

        if (isset($arguments["script"])) {
            $scriptPath = $outputPath . DIRECTORY_SEPARATOR . strtolower("action." . $arguments["name"]) . ".php";
            $arguments["script_name"] = strtolower($arguments["name"]);
            parent::main_render("action_script", $arguments, $scriptPath, $force);
        }

        return parent::main_render("action", $arguments, false);
    }

} 