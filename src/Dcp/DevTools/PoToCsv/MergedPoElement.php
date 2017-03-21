<?php

namespace Dcp\DevTools\PoToCsv;

/**
 * Class MergedPoElement
 * @package Dcp\DevTools\PoToCsv
 */
class MergedPoElement
{
    public $id;
    public $messages = [];
    public $contexts = [];
    public $metas = [];
    public $fileNames = [];

    /**
     * MergedPoElement constructor.
     * Create a MergedPoElement from a
     * PoElement and some information related
     * to its PoFile.
     *
     * @param PoElement $poElement.
     * @param string $fileName The name of the PoFile
     * the $poElement comes from.
     * @param string $elemLang The lang of the PoFile
     * the $poElement comes from.
     * @param string[] $langs.
     */
    public function __construct($poElement, $fileName, $elemLang, $langs)
    {
        $this->id = $poElement->id;

        foreach ($langs as $lang) {
            $this->messages[$lang] = '';
            $this->contexts[$lang] = '';
            $this->metas[$lang] = '';
            $this->fileNames[$lang] = '';
        }
        $this->messages[$elemLang] = $poElement->message;
        $this->contexts[$elemLang] = $poElement->context;
        $this->metas[$elemLang] = $poElement->meta;
        $this->fileNames[$elemLang] = $fileName;
    }
}
