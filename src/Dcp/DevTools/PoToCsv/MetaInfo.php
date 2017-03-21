<?php

namespace Dcp\DevTools\PoToCsv;

/**
 * Class MetaInfo
 * @package Dcp\DevTools\PoToCsv
 */
class MetaInfo
{
    public $fileName;
    public $header;
    public $headerMeta;
    public $trailingMeta;

    /**
     * MetaInfo constructor.
     *
     * @param PoFile $poFile The PoFile object you
     * want to retrieve the meta infos from.
     */
    public function __construct($poFile)
    {
        $this->fileName = $poFile->fileName;
        $this->header = $poFile->header;
        $this->headerMeta = $poFile->headerMeta;
        $this->trailingMeta = $poFile->trailingMeta;
    }
}
