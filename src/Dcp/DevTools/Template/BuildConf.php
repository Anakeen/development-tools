<?php

namespace Dcp\DevTools\Template;

use Dcp\DevTools\Utils\ConfigFile;

class BuildConf extends Template
{

    public function render($arguments, $outputPath, $force = false)
    {
        if (!empty($outputPath) && !is_dir($outputPath)) {
            throw new Exception("The output path $outputPath is not a dir");
        }
        if (!isset($arguments["name"]) || !$this->checkLogicalName($arguments["name"])) {
            throw new Exception("You need to set the name of the module with a valid name " . $this->logicalNameRegExp);
        }
        if (!isset($arguments["version"])) {
            $arguments["version"] = "1.0.0";
        }
        if (!isset($arguments["release"])) {
            $arguments["release"] = "0";
        }
        $arguments["applicationPath"] = array();
        if (isset($arguments["application"])) {
            $arguments["applicationPath"] = explode(",", $arguments["application"]);
        }
        $arguments["applicationPath"] = json_encode($arguments["applicationPath"]);
        $arguments["includedPath"] = array("locale");
        if (isset($arguments["external"])) {
            $arguments["includedPath"][] = "EXTERNALS";
        }
        if (isset($arguments["style"])) {
            $arguments["includedPath"][] = "STYLE";
        }
        if (isset($arguments["images"])) {
            $arguments["includedPath"][] = "Images";
        }
        if (isset($arguments["rest"])) {
            $arguments["includedPath"][] = "HTTPAPI_V1";
        }
        if (isset($arguments["lang"]) && is_array($arguments["lang"])) {
            $arguments["buildLang"] = json_encode($arguments["lang"]);
        }
        if ($arguments["enclosure"] === '"') {
            $arguments["enclosure"] = '\"';
        }
        $arguments["includedPath"] = json_encode($arguments["includedPath"]);
        if (!empty($outputPath)) {
            $outputPath .= DIRECTORY_SEPARATOR . ConfigFile::DEFAULT_FILE_NAME;
        }
        $devtool = join(
            DIRECTORY_SEPARATOR,
            array(__DIR__, '..', '..', '..', '..', 'version.json')
        );
        $devtool = json_decode(file_get_contents($devtool), true);
        $arguments["devtool"] = $devtool;
        return parent::main_render("build", $arguments, $outputPath, $force);
    }
}