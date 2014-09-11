<?php
namespace Dcp\DevTools\ExtractPo;

use Dcp\BuildTools\Po\AnalyzePhp;
use Dcp\BuildTools\Po\AnalyzeLayout;

class ApplicationPo extends PoGenerator
{

    public function extractPo()
    {
        if (isset($this->conf["application"])) {
            foreach ($this->conf["application"] as $currentApp) {
                $currentAppPath = $this->inputPath . DIRECTORY_SEPARATOR . $currentApp;
                $filesList = $this->globRecursive($currentAppPath . DIRECTORY_SEPARATOR . '*.php');
                $tempApp = tempnam(sys_get_temp_dir(), 'tmp_app_po_' . $currentApp);
                $extractor = new AnalyzePhp($tempApp, $this->gettextpath);
                $extractor->extract($filesList);
                $filesList = $this->globRecursive($currentAppPath . DIRECTORY_SEPARATOR . 'Layout' . DIRECTORY_SEPARATOR . '*');
                $tempLayout = tempnam(sys_get_temp_dir(), 'tmp_layout_' . $currentApp);
                $extractor = new AnalyzeLayout($tempLayout, $this->gettextpath);
                $extractor->extract($filesList);
                $tempFusion = tempnam(sys_get_temp_dir(), 'tmp_fusion_' . $currentApp);
                $this->xgettextWrapper->msgcat("-o $tempFusion $tempApp $tempLayout");

                foreach ($this->conf["lang"] as $currentLang) {
                    $this->updatePo($tempFusion, $currentApp."_".$currentLang, $currentLang);
                }
                unlink($tempApp);
                unlink($tempLayout);
                unlink($tempFusion);
            }
        }
    }
}