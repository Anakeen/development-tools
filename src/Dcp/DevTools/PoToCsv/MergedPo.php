<?php

namespace Dcp\DevTools\PoToCsv;

/**
 * Class MergedPo
 * @package Dcp\DevTools\PoToCsv
 */
class MergedPo
{
    public $langs = [];
    public $metaInfos = [];
    public $mergedPoElements = [];

    /**
     * MergedPo constructor.
     *
     * @param string[] $langs.
     */
    public function __construct($langs)
    {
        $this->langs = $langs;
    }
}
