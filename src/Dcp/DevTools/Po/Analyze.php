<?php

namespace Dcp\DevTools\Po;

class Analyze
{

    protected $outputFile;
    protected $getTextOptions = '--language=%s --sort-output --from-code=utf-8 --no-location --indent --add-comments=_COMMENT --keyword=___:1 --keyword=___:1,2c --keyword=n___:1,2 --keyword=pgettext:1c,2 --keyword=n___:1,2,4c --keyword=npgettext:1,2,4c --keyword="N_"  --keyword="text" -keyword="Text"';

    public function __construct($outputFile, $getTextPath = "", $getTextOptions = null)
    {
        $this->getTextOptions = sprintf($this->getTextOptions, "PHP");
        $this->outputFile = $outputFile;
        $this->xgetextWrapper = new XgettextWrapper($getTextPath);
        if ($getTextOptions !== null) {
            $this->getTextOptions = $getTextOptions;
        }
    }

    public function extract($filesPath)
    {
        $filesPath = array_map(function($path) { return escapeshellarg($path);}, $filesPath);
        if (!empty($filesPath)) {
            $options = $this->getTextOptions;
            $options .= " -o " . escapeshellarg($this->outputFile) . " ";
            $options .= join(" ", $filesPath);
            $this->xgetextWrapper->xgettext($options);
        }
    }

} 