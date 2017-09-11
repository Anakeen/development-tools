<?php

namespace Dcp\DevTools\CsvToPo;

use Dcp\DevTools\PoToCsv\PoElement;
use Dcp\DevTools\PoToCsv\PoFile;

/**
 * Class PoUnMerger
 *
 * @package Dcp\DevTools\CsvToPo
 */
class PoUnMerger
{
    public $mergedPo;

    /**
     * PoUnMerger constructor.
     *
     * @param \Dcp\DevTools\PoToCsv\MergedPo $mergedPo
     */
    public function __construct($mergedPo)
    {
        $this->mergedPo = $mergedPo;
    }

    /**
     * Convert a MergedPo Object into an array
     * of PoFile Objects and return it.
     *
     * @return PoFile[] An array containing PoFiles.
     */
    public function unMerge()
    {
        $poFiles = [];

        foreach ($this->mergedPo->mergedPoElements as $mergedPoElement) {
            foreach ($mergedPoElement->fileNames as $lang => $fileName) {
                if ($fileName !== null) {
                    $poElement = new PoElement();
                    $poElement->id = $mergedPoElement->id;
                    $poElement->meta = $mergedPoElement->metas[$lang];
                    $poElement->context = $mergedPoElement->contexts[$lang];
                    $poElement->message = $mergedPoElement->messages[$lang];

                    $index = $this->fileExistAt($fileName, $poFiles);
                    if ($index !== null) {
                        $poFiles[$index]->poElements[] = $poElement;
                    } else {
                        $poFile = new PoFile($fileName, $lang);
                        $poFile->poElements[] = $poElement;
                        $poFiles[] = $poFile;
                    }
                }
            }
        }

        foreach ($this->mergedPo->metaInfos as $metaInfo) {
            $index = $this->fileExistAt($metaInfo->fileName, $poFiles);

            if ($index !== null) {
                $poFiles[$index]->header = $metaInfo->header;
                $poFiles[$index]->headerMeta = $metaInfo->headerMeta;
                $poFiles[$index]->trailingMeta = $metaInfo->trailingMeta;
            } else {
                $poFile = new PoFile($metaInfo->fileName, $this->fileLang($metaInfo->fileName));
                $poFile->header = $metaInfo->header;
                $poFile->headerMeta = $metaInfo->headerMeta;
                $poFile->trailingMeta = $metaInfo->trailingMeta;
                $poFiles[] = $poFile;
            }
        }

        return $poFiles;
    }

    /**
     * Return the index position where $fileName ==
     * $poFiles[index]->fileName, null otherwise.
     *
     * @param string $fileName
     * @param PoFile[] $poFiles
     *
     * @return int|null The index where $fileName is in $PoFiles
     * or null if $PoFiles doesn't contains it.
     */
    public function fileExistAt($fileName, $poFiles)
    {
        for ($i = 0; $i < count($poFiles); $i++) {
            if ($fileName == $poFiles[$i]->fileName) {
                return $i;
            }
        }
        return null;
    }

    /**
     * Look for /xx/ and _xx in the $filePathName
     * where xx is a langage country code,
     * to deduce the language of the file.
     *
     * @param string $filePathName.
     *
     * @return string The language country code.
     */
    public function fileLang($filePathName)
    {
        if (strpos($filePathName, '/fr/') !== false || strpos($filePathName, '_fr') !== false) {
            return 'fr';
        } else {
            return 'en';
        }
    }
}
