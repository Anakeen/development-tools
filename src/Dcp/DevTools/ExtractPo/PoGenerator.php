<?php

namespace Dcp\DevTools\ExtractPo;

use Dcp\BuildTools\Po\XgettextWrapper;

class PoGenerator
{

    protected $conf = null;
    protected $inputPath = null;
    protected $xgettextWrapper = null;
    protected $gettextpath = null;

    public function __construct($inputPath)
    {
        if (!is_dir($inputPath)) {
            throw new Exception("The input path doesn't exist ($inputPath)");
        }
        $this->inputPath = $inputPath;
        if (!is_file($inputPath . DIRECTORY_SEPARATOR . 'build.json')) {
            throw new Exception("The build.json doesn't exist ($inputPath)");
        }
        $this->conf = json_decode(file_get_contents($inputPath . DIRECTORY_SEPARATOR . 'build.json'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("The build.json is not a valid JSON file ($inputPath)");
        }
        if (!isset($this->conf["moduleName"])) {
            throw new Exception("The build.json doesn't not contain the module name ($inputPath)");
        }
        $getTextPath = "";
        if (isset($this->conf["toolsPath"]) && isset($this->conf["toolsPath"]["getttext"])) {
            $getTextPath = $this->conf["toolsPath"]["getttext"];
        }
        if (!isset($this->conf["csvParam"])){
            $this->conf["csvParam"] = array();
        }
        if (!isset($this->conf["csvParam"]["enclosure"])) {
            $this->conf["csvParam"]["enclosure"]= '"';
        }
        if (!isset($this->conf["csvParam"]["delimiter"])) {
            $this->conf["csvParam"]["delimiter"] = ';';
        }
        $this->gettextpath = $getTextPath;
        $this->xgettextWrapper = new XgettextWrapper($getTextPath);
    }

    public function updatePo($potFile, $name, $lang)
    {
        $localePath = $this->inputPath . DIRECTORY_SEPARATOR . "locale";
        if (!is_dir($localePath)) {
            mkdir($this->inputPath . DIRECTORY_SEPARATOR . "locale");
        }
        $langPath = $localePath . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . "LC_MESSAGES" . DIRECTORY_SEPARATOR . "src";
        if (!is_dir($langPath)) {
            mkdir($langPath, 0777, true);
        }
        $poPath = $langPath . DIRECTORY_SEPARATOR . $name . ".po";
        if (!is_file($poPath)) {
            $this->xgettextWrapper->msginit(array("lang" => $lang, "potFile" => $potFile, "poTarget" => $poPath));
            //sprintf(" --locale=%s --no-translator --input=%s --output=%s", $lang, $potFile, $poPath));
        } else {
            $this->xgettextWrapper->msgmerge(sprintf(" --sort-output --no-fuzzy-matching -o %s %s %s", $poPath . '.new', $poPath, $potFile));
            rename($poPath . '.new', $poPath);
        }
    }

    protected function globRecursive($pattern, $flags = 0)
    {

        $files = glob($pattern, $flags);

        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, $this->globRecursive($dir . '/' . basename($pattern), $flags));
        }

        return $files;
    }

    protected function tempdir($prefix)
    {
        $tempfile = tempnam(sys_get_temp_dir(), $prefix);
        if (file_exists($tempfile)) {
            unlink($tempfile);
        }
        mkdir($tempfile);
        if (is_dir($tempfile)) {
            return $tempfile;
        }
        return false;
    }

    protected function unlinkDir($dirPath)
    {
        array_map('unlink', glob($dirPath . DIRECTORY_SEPARATOR . "*"));
        rmdir($dirPath);
    }

} 