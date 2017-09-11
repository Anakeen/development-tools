<?php

namespace Dcp\DevTools\Template;

use Dcp\DevTools\Utils\StringUtils;

class FamilyClass extends Template
{
    public function render($arguments, $outputDir, $fileName = '', $force = false)
    {
        if (!isset($arguments["namespace"]) || !$this->checkNamespace($arguments["namespace"])) {
            throw new Exception("You need to set the namespace of the family with a valid name " . $this->namespaceRegExp);
        }

        if (!isset($arguments["name"]) || !$this->checkLogicalName($arguments["name"])) {
            throw new Exception("You need to set the name of the family with a valid name " . $this->logicalNameRegExp);
        } else {
            $arguments["lowerName"] = ucfirst(strtolower($arguments["name"]));
            $arguments["className"] = StringUtils::normalizeClassName(strtolower($arguments["name"]));
        }

        if (isset($arguments["parent"]) && !$this->checkLogicalName($arguments["parent"])) {
            throw new Exception("You need to set the parent of the family with a valid name " . $this->logicalNameRegExp);
        }
        $arguments['parentFQN'] = '\\Dcp\\Family\\' . (isset($arguments["parent"]) ? ucfirst(strtolower($arguments["parent"])) : 'Document');

        if(false === $outputDir) {
            $outputFile = false;
        } else {
            if(!is_dir($outputDir)) {
                throw new Exception("The output dir $outputDir is not a dir");
            }
            $outputFile = $outputDir . DIRECTORY_SEPARATOR . $arguments["className"] . ".php";
        }

        return parent::mainRender("family_class", $arguments, $outputFile, $force);
    }
}
