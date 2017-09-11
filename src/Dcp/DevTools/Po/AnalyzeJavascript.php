<?php
namespace Dcp\DevTools\Po;

class AnalyzeJavascript extends Analyze
{
    public function __construct($outputFile, $getTextPath = "", $getTextOptions = null)
    {
        $this->getTextOptions = sprintf($this->getTextOptions, "JavaScript");
        parent::__construct($outputFile, $getTextPath, $getTextOptions);
    }

    public function extract(\Iterator $filesPath)
    {
        parent::extract($filesPath);
    }
}
