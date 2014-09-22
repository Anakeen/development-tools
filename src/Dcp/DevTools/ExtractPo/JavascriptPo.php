<?php
/**
 * Created by PhpStorm.
 * User: charles
 * Date: 11/09/14
 * Time: 13:33
 */

namespace Dcp\DevTools\ExtractPo;

use Dcp\BuildTools\Po\AnalyzeJavascript;

class JavascriptPo extends PoGenerator {

    public function extractPo()
    {
        if (isset($this->conf["application"])) {
            foreach ($this->conf["application"] as $currentApp) {
                $currentAppPath = $this->inputPath . DIRECTORY_SEPARATOR . $currentApp;
                $filesList = $this->globRecursive($currentAppPath . DIRECTORY_SEPARATOR . '*.js');
                if (empty($filesList)) {
                    continue;
                }
                $tempName = tempnam(sys_get_temp_dir(), 'tmp_js_layout_' . $currentApp);
                unlink($tempName);
                $tempName = $tempName.".pot";
                $extractor = new AnalyzeJavascript($tempName, $this->gettextpath);
                $extractor->extract($filesList);
                if (filesize($tempName) === 0) {
                    continue;
                }
                foreach ($this->conf["lang"] as $currentLang) {
                    $this->updatePo($tempName, "js_".$currentApp . "_" . $currentLang, $currentLang);
                }
                if (is_file($tempName)) {
                    unlink($tempName);
                }
            }
        }
    }

} 