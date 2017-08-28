<?php

namespace Dcp\DevTools\Po;

use Dcp\DevTools\Template\Template;

class AnalyzePhp extends Analyze
{
    public function extract(\Iterator $filesPath)
    {
        $searchLabels = [];
        $sharpLabels = [];
        $workflowLabels = [];

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
        $filesIterator = new \AppendIterator();
        $filesIterator->append($filesPath);
        $filesIterator->append((new \ArrayObject([$temporaryFile]))->getIterator());

        parent::extract($filesIterator);

        unset($temporaryFile);
    }
}
