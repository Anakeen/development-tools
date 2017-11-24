<?php
namespace Dcp\DevTools\ExtractPo;

use Dcp\DevTools\Po\AnalyzePhp;
use Dcp\DevTools\Po\AnalyzeLayout;
use Dcp\DevTools\Po\AnalyzeMustache;

class ModulePo extends PoGenerator
{
    protected $filesToBeGenerated=[];
    public function extractPo()
    {
        $inputPaths=[];
        $poModuleFile="";
        if (!empty($this->conf["po"]["php"])) {
            $inputPaths=$this->conf["po"]["php"];
            $poModuleFile=$this->conf["moduleName"];

        } 

        if ($inputPaths) {
            if (isset($this->conf["po"]["layout"])) {
                $layoutPath = $this->conf["po"]["layout"];
                foreach ($layoutPath as  $inputPath) {
                    $this->extractLayoutPoFromDir($inputPath);
                }
            }


            foreach ($inputPaths as  $inputPath) {
                    $this->extractPoFromDir($inputPath, $poModuleFile);
            }
            if (!empty($this->filesToBeGenerated)) {
                $firstFile = array_shift($this->filesToBeGenerated);
                foreach ($this->conf["lang"] as $currentLang) {
                    $tempFusion = tempnam(sys_get_temp_dir(), 'tmp_fusion_' . $poModuleFile);
                    unlink($tempFusion);
                    $tempFusion = $tempFusion . ".po";
                    $this->xgettextWrapper->msginit(array(
                        "lang" => $currentLang,
                        "potFile" => $firstFile,
                        "poTarget" => $tempFusion,
                        "name" => $poModuleFile
                    ));

                    foreach ($this->filesToBeGenerated as $currentFile) {
                        $this->xgettextWrapper->msgcat("-o $tempFusion --use-first $tempFusion $currentFile");
                    }
                    $this->updatePo($tempFusion, $poModuleFile . "_" . $currentLang, $currentLang);
                    unlink($tempFusion);
                }
                if (is_file($firstFile)) {
                    unlink($firstFile);
                }
            }

            foreach ($this->filesToBeGenerated as $currentFile) {
                unlink($currentFile);
            }
        }
    }

    protected function extractLayoutPoFromDir($inputPath) {

        $layoutDir = $this->inputPath . DIRECTORY_SEPARATOR . $inputPath;



        if (is_dir($layoutDir)) {
            $filesList = $this->globRecursive($layoutDir, '/.*/');
            $tempLayout = tempnam(sys_get_temp_dir(), 'tmp_layout_' . $inputPath);
            unlink($tempLayout);
            $extractor = new AnalyzeLayout($tempLayout . ".pot", $this->gettextpath);
            $extractor->extract($filesList);

            if (is_file($tempLayout . ".pot")) {
                $this->filesToBeGenerated[] = $tempLayout . ".pot";

            }
        }


      
    }
    protected function extractPoFromDir($inputPath, $poFile) {

        $currentAppPath = $this->inputPath . DIRECTORY_SEPARATOR . $inputPath;


        $filesList = $this->globRecursive($currentAppPath, '/^.*\.app$/');
        $tempApp = tempnam(sys_get_temp_dir(), 'tmp_app_po_' . $inputPath);
        unlink($tempApp);
        $extractor = new AnalyzePhp($tempApp . ".pot", $this->gettextpath);
        $extractor->extract($filesList);

        $filesList = $this->globRecursive($currentAppPath, '/^.*\.php$/');
        $tempPhp = tempnam(sys_get_temp_dir(), 'tmp_php_po_' . $inputPath);
        unlink($tempPhp);
        $extractor = new AnalyzePhp($tempPhp . ".pot", $this->gettextpath);
        $extractor->extract($filesList);

    

        $filesList = $this->globRecursive($currentAppPath, '/^.*\.mustache$/');

        $tempDdui = tempnam(sys_get_temp_dir(), 'tmp_ddui_' . $poFile);
        unlink($tempDdui);
        $extractor = new AnalyzeMustache($tempDdui . ".pot", $this->gettextpath);
        $extractor->extract($filesList);


        if (is_file($tempApp . ".pot")) {
            $this->filesToBeGenerated[] = $tempApp . ".pot";
        }

        if (is_file($tempPhp . ".pot")) {
            $this->filesToBeGenerated[] = $tempPhp . ".pot";
        }


        if (is_file($tempDdui . ".pot")) {
            $this->filesToBeGenerated[] = $tempDdui . ".pot";
        }

       
    }
}
