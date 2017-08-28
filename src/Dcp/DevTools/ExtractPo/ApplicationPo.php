<?php
namespace Dcp\DevTools\ExtractPo;

use Dcp\DevTools\Po\AnalyzePhp;
use Dcp\DevTools\Po\AnalyzeLayout;
use Dcp\DevTools\Po\AnalyzeMustache;

class ApplicationPo extends PoGenerator
{
    public function extractPo()
    {
        if (isset($this->conf["application"])) {
            foreach ($this->conf["application"] as $currentApp) {
                $currentAppPath = $this->inputPath . DIRECTORY_SEPARATOR . $currentApp;

                $filesList = $this->globRecursive($currentAppPath, '/^.*\.app$/');
                $tempApp = tempnam(sys_get_temp_dir(), 'tmp_app_po_' . $currentApp);
                unlink($tempApp);
                $extractor = new AnalyzePhp($tempApp . ".pot", $this->gettextpath);
                $extractor->extract($filesList);

                $filesList = $this->globRecursive($currentAppPath, '/^.*\.php$/');
                $tempPhp = tempnam(sys_get_temp_dir(), 'tmp_php_po_' . $currentApp);
                unlink($tempPhp);
                $extractor = new AnalyzePhp($tempPhp . ".pot", $this->gettextpath);
                $extractor->extract($filesList);

                $filesList = $this->globRecursive($currentAppPath . DIRECTORY_SEPARATOR . 'Layout', '/.*/');
                $tempLayout = tempnam(sys_get_temp_dir(), 'tmp_layout_' . $currentApp);
                unlink($tempLayout);
                $extractor = new AnalyzeLayout($tempLayout . ".pot", $this->gettextpath);
                $extractor->extract($filesList);

                $filesList = $this->globRecursive($currentAppPath, '/^.*\.mustache$/');
                $tempDdui = tempnam(sys_get_temp_dir(), 'tmp_ddui_' . $currentApp);
                unlink($tempDdui);
                $extractor = new AnalyzeMustache($tempDdui . ".pot", $this->gettextpath);
                $extractor->extract($filesList);

                $filesToBeGenerated = array();

                if (is_file($tempApp . ".pot")) {
                    $filesToBeGenerated[] = $tempApp . ".pot";
                }

                if (is_file($tempPhp . ".pot")) {
                    $filesToBeGenerated[] = $tempPhp . ".pot";
                }

                if (is_file($tempLayout . ".pot")) {
                    $filesToBeGenerated[] = $tempLayout . ".pot";
                }

                if (is_file($tempDdui . ".pot")) {
                    $filesToBeGenerated[] = $tempDdui . ".pot";
                }

                if (!empty($filesToBeGenerated)) {
                    $firstFile = array_shift($filesToBeGenerated);
                    foreach ($this->conf["lang"] as $currentLang) {
                        $tempFusion = tempnam(sys_get_temp_dir(), 'tmp_fusion_' . $currentApp);
                        unlink($tempFusion);
                        $tempFusion = $tempFusion . ".po";
                        $this->xgettextWrapper->msginit(array("lang" => $currentLang, "potFile" => $firstFile, "poTarget" => $tempFusion, "name" => $currentApp));
                        foreach ($filesToBeGenerated as $currentFile) {
                            $this->xgettextWrapper->msgcat("-o $tempFusion --use-first $tempFusion $currentFile");
                        }
                        $this->updatePo($tempFusion, $currentApp . "_" . $currentLang, $currentLang);
                        unlink($tempFusion);
                    }
                    if (is_file($firstFile)) {
                        unlink($firstFile);
                    }
                }

                foreach ($filesToBeGenerated as $currentFile) {
                    unlink($currentFile);
                }
            }
        }
    }
}
