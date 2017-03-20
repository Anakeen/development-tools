<?php

namespace Dcp\DevTools\PoToCsv;

/**
 * Class PoMerger
 * @package Dcp\DevTools\PoToCsv
 */
class PoMerger
{
    public $langs = [];

    public function __construct($langs)
    {
        $this->langs = $langs;
    }

    public function merge($fileNames)
    {
        $mergedPo = new MergedPo($this->langs);

        foreach ($fileNames as $fileName) {
            $poParser = new PoParser($fileName);
            $poFile = $poParser->parse();

            $mergedPo->metaInfos[] = new MetaInfo($poFile);

            foreach ($poFile->poElements as $poElement) {
                $index = $this->idExistAt($poElement->id, $mergedPo->mergedPoElements);
                if ($index !== null) {
                    $mergedPo->mergedPoElements[$index] =
                        $this->mergePoElements(
                            $poElement,
                            $poFile->fileName,
                            $poFile->lang,
                            $mergedPo->mergedPoElements[$index]
                        );
                } else {
                    $mergedPo->mergedPoElements[] =
                        new MergedPoElement(
                            $poElement,
                            $poFile->fileName,
                            $poFile->lang,
                            $this->langs
                        );
                }
            }
        }

        return $mergedPo;
    }

    public function idExistAt($id, $poElements)
    {
        for ($i = 0; $i < count($poElements); $i++) {
            if ($poElements[$i]->id == $id) {
                return $i;
            }
        }
        return null;
    }

    public function mergePoElements($poElement, $fileName, $lang, $mergedPoElement)
    {
        $mergedPoElement->messages[$lang] = $poElement->message;
        $mergedPoElement->contexts[$lang] = $poElement->context;
        $mergedPoElement->metas[$lang] = $poElement->meta;
        $mergedPoElement->fileNames[$lang] = $fileName;

        return $mergedPoElement;
    }
}
