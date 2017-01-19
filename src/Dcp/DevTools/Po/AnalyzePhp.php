<?php

namespace Dcp\DevTools\Po;

use Dcp\DevTools\Template\Template;

class AnalyzePhp extends Analyze
{
    public function extract($filesPath)
    {
        $searchLabels = [];
        $sharpLabels = [];
        $workflowLabels = [];

        //Remove blank elements
        $filesPath = array_filter($filesPath, function ($value) {
            return trim($value);
        });

        foreach ($filesPath as $phpInputFile) {
            $extractor = new extractPhp($phpInputFile);
            $sharpLabels = array_merge($sharpLabels, $extractor->extractSharpLabels());
            $searchLabels = array_merge($searchLabels, $extractor->extractSearchLabels());
            $workflowLabels = array_merge($workflowLabels, $extractor->extractWorkflowLabels());
        }

        ksort($sharpLabels);
        ksort($searchLabels);
        ksort($workflowLabels);

        $temporaryFile = tempnam(sys_get_temp_dir(), "additionnal_keys_");

        $template = new Template();
        $template->mainRender(
            "temporary_php_file",
            array(
                "sharpLabels" => array_values($sharpLabels),
                "searchLabels" => array_values($searchLabels),
                "workflowLabels" => array_values($workflowLabels)
            ),
            $temporaryFile,
            true
        );
        $filesPath[] = $temporaryFile;

        parent::extract($filesPath);

        unset($temporaryFile);
    }
}
