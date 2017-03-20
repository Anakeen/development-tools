<?php

namespace Dcp\DevTools\PoToCsv;

/**
 * Class PoToCsv
 * @package Dcp\DevTools\PoToCsv
 */
class PoToCsv
{
    protected $options;

    public function __construct(array $options = [])
    {
        $missingOptions = [];

        if (!isset($options['source'])) {
            $missingOptions['source'] =
                'You need to set the path of the directory where to search for the .po files with -s or --source';
        }

        if (!isset($options['output'])) {
            $missingOptions['output'] =
                'You need to set the path of the .csv file to create with -o or --output';
        }

        if (0 < count($missingOptions)) {
            throw new Exception(
                sprintf(
                    "Missing options:\n%s",
                    "  - " . implode("\n  - ", $missingOptions)
                )
            );
        }

        $this->options = $options;
        $this->options['sourcePath'] = realpath($this->options['source']);
        $this->options['outputPath'] = $this->options['output'];
    }

    /**
     * Convert all the .po files found in $this->options['sourcePath']
     * (recursively searched) in a .csv file specified by $this->options['outputPath'].
     *
     * @return string[] Logs specifying what files have been converted.
     * @throws Exception
     */
    public function convert()
    {
        $fileSuccessfullyCreated = touch($this->options['outputPath']);

        if (!$fileSuccessfullyCreated) {
            throw new Exception(
                sprintf('Cannot create the file %s', $this->options['outputPath'])
            );
        }

        $poFileNames = $this->fileNamesWithExt($this->options['sourcePath'], 'po');

        $poMerger = new PoMerger(['fr', 'en']);
        $mergedPo = $poMerger->merge($poFileNames);

        $csvString = $this->mergedPoToCsvString($mergedPo, $this->options['sourcePath']);

        file_put_contents($this->options['outputPath'], $csvString);

        return [
            'convertedPoFiles' => $poFileNames,
        ];
    }

    public function mergedPoToCsvString($mergedPo, $projectRootDir)
    {
        $csvHeader = "id;";
        foreach ($mergedPo->langs as $lang) {
            $csvHeader .= $lang . ";";
        }
        foreach ($mergedPo->langs as $lang) {
            $csvHeader .= "context " . $lang . ";";
        }
        foreach ($mergedPo->langs as $lang) {
            $csvHeader .= "meta " . $lang . ";";
        }
        foreach ($mergedPo->langs as $lang) {
            $csvHeader .= "file " . $lang . ";";
        }
        $csvHeader .= "\n";

        $csvMergedPoElements = '';
        foreach ($mergedPo->mergedPoElements as $elem) {
            $csvMergedPoElements .= $this->toCsvString($elem->id);

            foreach ($mergedPo->langs as $lang) {
                $csvMergedPoElements .= $this->toCsvString($elem->messages[$lang]);
            }
            foreach ($mergedPo->langs as $lang) {
                $csvMergedPoElements .= $this->toCsvString($elem->contexts[$lang]);
            }
            foreach ($mergedPo->langs as $lang) {
                $csvMergedPoElements .= $this->toCsvString($elem->metas[$lang]);
            }
            foreach ($mergedPo->langs as $lang) {
                $csvMergedPoElements .= $this->toCsvString($this->keepAfter($projectRootDir, $elem->fileNames[$lang]));
            }
            $csvMergedPoElements .= "\n";
        }

        $warning = "\n\n\n\n\n\nIGNORE THE FOLLOWING LINES\n";
        $csvMetaInfoHeader = "file;header;header meta;trailing meta;\n";

        $csvMetaInfo = '';
        foreach ($mergedPo->metaInfos as $metaInfo) {
            $csvMetaInfo .= $this->toCsvString($this->keepAfter($projectRootDir, $metaInfo->fileName)) .
                            $this->toCsvString($metaInfo->header) .
                            $this->toCsvString($metaInfo->headerMeta) .
                            $this->toCsvString($metaInfo->trailingMeta) . "\n";
        }

        return $csvHeader . $csvMergedPoElements . $warning . $csvMetaInfoHeader . $csvMetaInfo;
    }

    public function toCsvString($string)
    {
        return '"' . str_replace('"', '""', $string) . '";';
    }

    /**
     * Return true if $fileName end with the extension $extension
     * false otherwise.
     * E.g: if $extension is 'csv', check if $fileName end with '.csv'.
     *
     * @param string $fileName The string to check for the $extension.
     * @param string $extension The string to check for the $extension.
     * @return bool true if $fileName end with the extension $extension
     * false otherwise.
     */
    public function haveExtension($fileName, $extension)
    {
        $charsToCheck = '.' . $extension;
        $i = strlen($fileName) - 1;
        $j = strlen($charsToCheck) - 1;

        if ($i < $j) {
            return false;
        }

        for (; $j >= 0; $i--, $j--) {
            if ($fileName[$i] != $charsToCheck[$j]) {
                return false;
            }
        }
        return true;
    }

    /**
     * Return all files (and directories) found in $directory
     * except ./ and ../ (not recursive).
     *
     * @param string $directory The directory path to search files.
     * @return string[] File path of the files.
     */
    public function filesWithoutSpecialDirsIn($directory)
    {
        return array_diff(scandir($directory), ['.', '..']);
    }

    /**
     * Return all files found in $directory with the extension $extension,
     * (recursively search in the underlying directories).
     *
     * @param string $directory The directory path to search for files.
     * @return string[] File path of the files.
     */
    public function fileNamesWithExt($directory, $extension)
    {
        $fileNames = [];

        foreach ($this->filesWithoutSpecialDirsIn($directory) as $fileName) {
            $fileName = $directory . DIRECTORY_SEPARATOR . $fileName;
            if (is_dir($fileName)) {
                $fileNames = array_merge(
                    $fileNames,
                    $this->fileNamesWithExt($fileName, $extension)
                );
            } elseif ($this->haveExtension($fileName, $extension)) {
                $fileNames[] = $fileName;
            }
        }
        return $fileNames;
    }

    /**
     * Return the part of the string after $target
     * E.g: $target is 'dog' $source is 'dogcat', 'cat' is returned.
     *
     * @param string $target The string used as a point
     * to truncate the $source string.
     * @param string $source The string to truncate.
     * @return string The remaining string.
     */
    public function keepAfter($target, $source)
    {
        return substr(strstr($source, $target), strlen($target));
    }
}
