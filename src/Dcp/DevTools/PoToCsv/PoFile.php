<?php

namespace Dcp\DevTools\PoToCsv;

/**
 * Class PoFile
 * @package Dcp\DevTools\PoToCsv
 */
class PoFile
{
    public $lang;
    public $fileName;
    public $poElements = [];
    public $header = '';
    public $headerMeta = '';
    public $trailingMeta = '';

    /**
     * PoFile constructor.
     *
     * @param string $fileName.
     * @param string $lang.
     */
    public function __construct($fileName, $lang)
    {
        $this->fileName = $fileName;
        $this->lang = $lang;
    }
}
