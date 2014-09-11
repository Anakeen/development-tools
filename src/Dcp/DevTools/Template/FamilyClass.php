<?php

namespace Dcp\DevTools\Template;

class FamilyClass extends Template{

    public function render($arguments, $outputPath, $force = false)
    {
        if (!empty($outputPath) && !is_dir($outputPath)) {
            throw new Exception("The output path $outputPath is not a dir");
        }
        if (!isset($arguments["name"]) || !$this->checkLogicalName($arguments["name"])) {
            throw new Exception("You need to set the name of the family with a valid name " . $this->logicalNameRegExp);
        }
        if (!isset($arguments["namespace"]) || !$this->checkLogicalName($arguments["namespace"])) {
            throw new Exception("You need to set the namespace of the family with a valid name " . $this->logicalNameRegExp);
        }
        if (isset($arguments["parent"]) && !$this->checkLogicalName($arguments["parent"])) {
            throw new Exception("You need to set the parent of the family with a valid name " . $this->logicalNameRegExp);
        }
        if (!empty($outputPath)) {
            $outputPath .= DIRECTORY_SEPARATOR . $arguments["name"] . "__CLASS.php";
        }
        return parent::render("family_class", $arguments, $outputPath, $force);
    }
} 