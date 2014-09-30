<?php

namespace Dcp\DevTools\ExtractPo;

use Dcp\DevTools\Po\AnalyzePhp;

class ModulePo extends PoGenerator {

    public function extractPo()
    {
        if (isset($this->conf["includedPath"])) {
            $filesList = array();
            foreach ($this->conf["includedPath"] as $currentApp) {
                $filesList = array_merge( $filesList,
                    $this->globRecursive($this->inputPath. DIRECTORY_SEPARATOR . $currentApp . DIRECTORY_SEPARATOR . '*.php'));
            }
            if (empty($filesList)) {
                return;
            }
            $tempModule = tempnam(sys_get_temp_dir(), 'tmp_module_po_' . $this->conf["moduleName"]);
            unlink($tempModule);
            $extractor = new AnalyzePhp($tempModule.".pot", $this->gettextpath);
            $extractor->extract($filesList);
            if (is_file($tempModule . ".pot")) {
                foreach ($this->conf["lang"] as $currentLang) {
                    $this->updatePo($tempModule, $this->conf["moduleName"], $currentLang);
                }
            }
        }
    }
} 