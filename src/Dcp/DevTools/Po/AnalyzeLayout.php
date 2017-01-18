<?php

namespace Dcp\DevTools\Po;

use Dcp\DevTools\Template\Template;

class AnalyzeLayout extends Analyze
{
    public function extractODT($filePath)
    {
        $content = "";
        $contentFile = fopen("zip://$filePath#content.xml", "r");
        if ($contentFile === false) {
            //throw new Exception("Unable to read the content.xml of $filePath");
            return array();
        }
        while (!feof($contentFile)) {
            $content .= fread($contentFile, 2);
        }
        fclose($contentFile);
        if (!empty($content)) {
            $content .= "/n";
        }
        $contentFile = fopen("zip://$filePath#meta.xml", "r");
        if ($contentFile === false) {
            throw new Exception("Unable to read the content.xml of $filePath");
        }
        while (!feof($contentFile)) {
            $content .= fread($contentFile, 2);
        }
        fclose($contentFile);
        return $this->analyzeLayoutString($content);
    }

    public function extractLayout($filePath)
    {
        $content = file_get_contents($filePath);
        $return = $this->analyzeLayoutString($content);
        return $return;
    }

    public function analyzeLayoutString($content)
    {
        $return = array(
            "getText" => array(),
            "contextGetText" => array()
        );
        //Extract the non context gettext pattern [TEXT:key]
        $result = preg_match_all('/\[TEXT:(?P<key>[^\]]*)\]/', $content, $getTextElements, PREG_SET_ORDER);
        if ($result) {
            $gettextElements = array_map(function ($value) {
                return array("key" => str_replace('"', '\"', $value["key"]));
            }, $getTextElements);
            $return["getText"] = $gettextElements;
        }
        //Extract context gettext pattern [TEXT(context):key]
        $result = preg_match_all('/\[TEXT\((?P<context>[^\)]+)\):(?P<key>[^\]]*)\]/', $content, $getTextElements, PREG_SET_ORDER);
        if ($result) {
            $gettextElements = array_map(function ($value) {
                return array("key" => str_replace('"', '\"', $value["key"]), "context" => $value["context"]);
            }, $getTextElements);
            $return["contextGetText"] = $gettextElements;
        }
        return $return;
    }

    public function extract($filesPath)
    {
        $odtLayout = array();
        $layout = array();
        //Remove blank elements
        $filesPath = array_filter($filesPath, function ($value) {
            return trim($value);
        });
        foreach ($filesPath as $currentFile) {
            $extension = pathinfo($currentFile, PATHINFO_EXTENSION);
            if (strtolower($extension) === "odt") {
                $odtLayout = array_merge_recursive($odtLayout, $this->extractODT($currentFile));
            } else {
                $layout = array_merge_recursive($layout, $this->extractLayout($currentFile));
            }
        }

        $temporaryFile = tempnam(sys_get_temp_dir(), "additionnal_keys_");

        $template = new Template();
        $template->mainRender("temporary_layout_file",
            array("odtLayoutKeys" => $odtLayout, "layoutKeys" => $layout),
            $temporaryFile, true);
        $filesPath[] = $temporaryFile;
        parent::extract($filesPath);

        unset($temporaryFile);
    }
}
