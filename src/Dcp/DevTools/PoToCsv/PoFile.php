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
    public $poElements;
    public $header;
    public $trailingMeta;

    public function __construct($fileName)
    {
    }
}
