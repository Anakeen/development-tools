<?php
/**
 * Created by PhpStorm.
 * User: charles
 * Date: 11/09/14
 * Time: 13:36
 */

namespace Dcp\DevTools\ExtractPo;

use Dcp\DevTools\Po\AnalyzeFamily;

class FamilyPo extends PoGenerator
{
    public function extractPo()
    {
        if (isset($this->conf["application"])) {
            foreach ($this->conf["application"] as $currentApp) {
                $currentAppPath = $this->inputPath . DIRECTORY_SEPARATOR . $currentApp;
                $filesList = $this->globRecursive($currentAppPath . DIRECTORY_SEPARATOR . '*__STRUCT.csv');
                $tempStruct = $this->tempdir('tmp_family_struct_' . $currentApp);
                $extractor = new AnalyzeFamily($tempStruct, $this->conf["csvParam"]["enclosure"], $this->conf["csvParam"]["delimiter"]);
                $extractor->extract($filesList);
                $potList = $this->globRecursive($tempStruct.DIRECTORY_SEPARATOR."*.pot");
                $filesList = $this->globRecursive($currentAppPath . DIRECTORY_SEPARATOR .'*__PARAM.csv');
                $tempParam = $this->tempdir('tmp_family_param_' . $currentApp);
                $extractor = new AnalyzeFamily($tempParam, $this->conf["csvParam"]["enclosure"], $this->conf["csvParam"]["delimiter"]);
                $extractor->extract($filesList);
                $potList = array_merge($potList, $this->globRecursive($tempParam . DIRECTORY_SEPARATOR . "*.pot"));
                $tempFusion = $this->tempdir('tmp_fusion_' . $currentApp);
                foreach ($potList as $currentPot) {
                    $baseName = pathinfo($currentPot, PATHINFO_BASENAME);
                    if (!is_file($tempFusion . DIRECTORY_SEPARATOR . $baseName)) {
                        rename($currentPot, $tempFusion . DIRECTORY_SEPARATOR . $baseName);
                    } else {
                        $this->xgettextWrapper->msgcat("-o ".$tempFusion . DIRECTORY_SEPARATOR . $baseName.".new $currentPot ".$tempFusion . DIRECTORY_SEPARATOR . $baseName);
                        rename($tempFusion . DIRECTORY_SEPARATOR . $baseName . ".new", $tempFusion . DIRECTORY_SEPARATOR . $baseName);
                    }
                }
                $potList = $this->globRecursive($tempFusion . DIRECTORY_SEPARATOR . "*.pot");
                foreach ($potList as $currentPot) {
                    $fileName = pathinfo($currentPot, PATHINFO_FILENAME);
                    foreach ($this->conf["lang"] as $currentLang) {
                        $this->updatePo($currentPot, $fileName . "_" . $currentLang, $currentLang);
                    }
                }
                $this->unlinkDir($tempStruct);
                $this->unlinkDir($tempParam);
                $this->unlinkDir($tempFusion);
            }
        }
    }
}
