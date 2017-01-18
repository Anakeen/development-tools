<?php

namespace Dcp\DevTools\ExtractPo;

use Dcp\DevTools\Po\XgettextWrapper;
use Dcp\DevTools\Utils\ConfigFile;

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

        $config = new ConfigFile($inputPath);

        if (is_null($config->get('moduleName'))) {
            throw new Exception(
                sprintf(
                    "%s doesn't not contain the module name.",
                    $config->getConfigFilePath()
                )
            );
        }

        $this->conf = array_replace_recursive($config->getConfig(), [
            'toolsPath' => [
                'gettext' => ""
            ],
            'csvParam' => [
                'enclosure' => '"',
                'delimiter' => ';'
            ]
        ]);

        $this->gettextpath = isset($this->conf['toolsPath']['getttext']) ? $this->conf['toolsPath']['getttext'] : $this->conf['toolsPath']['gettext'];
        $this->xgettextWrapper = new XgettextWrapper($this->gettextpath);
    }

    public function updatePo($potFile, $name, $lang, $jsPo = false)
    {
        $localePath = $this->inputPath . DIRECTORY_SEPARATOR . "locale";
        if (!is_dir($localePath)) {
            mkdir($this->inputPath . DIRECTORY_SEPARATOR . "locale");
        }
        $langPath = $localePath . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR
            . "LC_MESSAGES" . DIRECTORY_SEPARATOR . "src";
        if ($jsPo) {
            $langPath =  $localePath . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR
                . "js" . DIRECTORY_SEPARATOR . "src";
        }
        if (!is_dir($langPath)) {
            mkdir($langPath, 0777, true);
        }
        $poPath = $langPath . DIRECTORY_SEPARATOR . $name . ".po";
        if (!is_file($poPath)) {
            $this->xgettextWrapper->msginit(array("lang" => $lang, "potFile" => $potFile, "poTarget" => $poPath, "name" => $name));
        } else {
            $this->xgettextWrapper->msgmerge(sprintf(" --sort-output --no-fuzzy-matching -o %s %s %s", escapeshellarg($poPath . '.new'), escapeshellarg($poPath), escapeshellarg($potFile)));
            rename($poPath . '.new', $poPath);
        }
    }

    protected function globRecursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);

        foreach (glob(dirname($pattern) . DIRECTORY_SEPARATOR .'*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, $this->globRecursive($dir . DIRECTORY_SEPARATOR . basename($pattern), $flags));
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
