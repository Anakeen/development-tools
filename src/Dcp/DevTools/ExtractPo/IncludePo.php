<?php

namespace Dcp\DevTools\ExtractPo;

use Dcp\DevTools\Po\AnalyzePhp;

class IncludePo extends PoGenerator
{
    public function extractPo()
    {
        if (empty($this->conf["po"]) && isset($this->conf["includedPath"])) {
            $filesList = new \AppendIterator();
            foreach ($this->conf["includedPath"] as $currentApp) {
                if (preg_match("/(.*)\/\*$/", $currentApp, $reg)) {
                    $currentApp = $reg[1];
                }
                $filesList->append($this->globRecursive($this->inputPath . DIRECTORY_SEPARATOR . $currentApp,
                    '/^.*\.php$/'));

            }
            if (0 === count($filesList)) {
                return;
            }
            $tempModule = tempnam(sys_get_temp_dir(), 'tmp_module_po_' . $this->conf["moduleName"]);
            unlink($tempModule);
            $extractor = new AnalyzePhp($tempModule . ".pot", $this->gettextpath);
            $extractor->extract($filesList);
            if (is_file($tempModule . ".pot")) {
                foreach ($this->conf["lang"] as $currentLang) {
                    $this->updatePo($tempModule, $this->conf["moduleName"], $currentLang);
                }
            }
        }
    }
}
