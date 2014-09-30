<?php

namespace Dcp\DevTools\Template;

class WorkflowStructure extends Template
{
    public function render($arguments, $outputPath, $force = false)
    {
        if (!empty($outputPath) && !is_dir($outputPath)) {
            throw new Exception("The output path $outputPath is not a dir");
        }
        if (!isset($arguments["name"]) || !$this->checkLogicalName($arguments["name"])) {
            throw new Exception("You need to set the name of the workflow with a valid name " . $this->logicalNameRegExp);
        }
        if (!isset($arguments["namespace"]) || !$this->checkLogicalName($arguments["namespace"])) {
            throw new Exception("You need to set the namespace of the workflow with a valid name " . $this->logicalNameRegExp);
        }
        if (isset($arguments["parent"]) && !$this->checkLogicalName($arguments["parent"])) {
            throw new Exception("You need to set the parent of the workflow with a valid name " . $this->logicalNameRegExp);
        }
        if (!isset($arguments["title"])) {
            $arguments["title"] = $arguments["name"]."__WFL";
        }
        if (!empty($outputPath)) {
            $outputPath .= DIRECTORY_SEPARATOR . $arguments["name"] . "__WFL.csv";
        }
        return parent::main_render("workflow_struct", $arguments, $outputPath, $force);
    }
} 