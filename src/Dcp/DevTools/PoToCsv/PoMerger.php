<?php

namespace Dcp\DevTools\PoToCsv;

/**
 * Class PoMerger
 * @package Dcp\DevTools\PoToCsv
 */
class PoMerger
{
    public $langs = [];

    /**
     * PoMerger constructor.
     *
     * @param string[] $langs.
     */
    public function __construct($langs)
    {
        $this->langs = $langs;
    }

    /**
     * Create a MergedPo object that is
     * the fusion of the .po files $fileNames and
     * return it.
     *
     * @param string[] $fileNames.
     *
     * @return MergedPo.
     */
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

    /**
     * Compare the $id with each PoElement->id
     * in $poElements, if the same id is found,
     * return the index of the PoElement in
     * $poElement, null otherwise.
     *
     * @param string $id.
     * @param PoElement[] $poElements.
     *
     * @return int|null.
     */
    public function idExistAt($id, $poElements)
    {
        for ($i = 0; $i < count($poElements); $i++) {
            if ($poElements[$i]->id == $id) {
                return $i;
            }
        }
        return null;
    }

    /**
     * Merge $poElement with $mergedPoElement,
     * return the resulting MergedPoElement.
     * E.g: take a MergedPoElement that have been
     * created with a PoElement coming from an
     * english PoFile and add the message[fr],
     * context[fr]... of a PoElement coming from
     * a french PoFile.
     *
     * @param PoELement $poElement.
     * @param string $fileName.
     * @param string $lang.
     * @param MergedPoElement $mergedPoElement.
     *
     * @return MergedPoElement.
     */
    public function mergePoElements($poElement, $fileName, $lang, $mergedPoElement)
    {
        $mergedPoElement->messages[$lang] = $poElement->message;
        $mergedPoElement->contexts[$lang] = $poElement->context;
        $mergedPoElement->metas[$lang] = $poElement->meta;
        $mergedPoElement->fileNames[$lang] = $fileName;

        return $mergedPoElement;
    }
}
