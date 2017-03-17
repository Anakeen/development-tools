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

        $poFiles = $this->fileNamesWithExt($this->options['sourcePath'], 'po');

        $result = $this->poFilesToArray($poFiles);
        $poArray = $result['poArray'];
        $headers = $result['headers'];
        $trailingMetas = $result['trailingMetas'];

    /* // TODO: Handle the msgctxt to have same id several times */
    /* foreach ($unifiedPoArray as $key => $value) { */
    /*     if (isset($value['context_en'])) { */
    /*         $unifiedPoArray[$key . "(" . $value['context_en'] . ")"] = $value; */
    /*         unset($value['context_en']); */

    /*         if (!isset($value['context_fr'])) { */
    /*             unset($unifiedPoArray[$key]); */
    /*         } */
    /*     } */
        /*     if (isset($value['context_fr'])) { */
        /*         $unifiedPoArray[$key . "(" . $value['context_fr'] . ")"] = $value; */
        /*         unset($value['context_fr']); */

        /*         if (!isset($value['context_en'])) { */
        /*             unset($unifiedPoArray[$key]); */
        /*         } */
        /*     } */
        /* } */

        $csvString = $this->poArrayToCsvString($poArray);
        $csvString .= $this->headersToCsvString($headers);
        $csvString .= $this->trailingMetasToCsvString($trailingMetas);

        file_put_contents($this->options['outputPath'], $csvString);

        return [
            'convertedPoFiles' => $poFiles,
        ];
    }

    public function poFileToArray($fileName)
    {
        $truncatedFileName = $this->keepAfter('afnor-opera/', $fileName);
        $lang = $this->fileLang($truncatedFileName);
        $fileKey = 'file_' . $lang;
        $msgctxtKey = 'context_' . $lang;
        $metaContentKey = 'meta_' . $lang;

        $fileContent = file_get_contents($fileName);
        $contentLength = strlen($fileContent);
        $pos = 0;
        $poArray = [];
        $header = [];
        $trailingMeta = [];

        while ($pos < $contentLength) {
            $result = $this->nextMetaContent($fileContent, $contentLength, $pos);
            $pos = $result['newPos'];
            $metaContent = $result['metaContent'];

            $result = $this->nextToken($fileContent, $contentLength, $pos);
            $token = $result['token'];
            $pos = $result['newPos'];

            $msgctxt = '';
            if ($token == 'msgctxt') {
                $result = $this->nextString($fileContent, $contentLength, $pos);
                $pos = $result['newPos'];
                $msgctxt = $result['msg'];

                $result = $this->nextToken($fileContent, $contentLength, $pos);
                $pos = $result['newPos'];
            }

            $result = $this->nextString($fileContent, $contentLength, $pos);
            $pos = $result['newPos'];
            $msgid = $result['msg'];

            $result = $this->nextToken($fileContent, $contentLength, $pos);
            $pos = $result['newPos'];

            $result = $this->nextString($fileContent, $contentLength, $pos);
            $pos = $result['newPos'];
            $msgstr = $result['msg'];

            if ($msgid == '') {
                if (count($header) == 0) {
                    $header = ['file' => $truncatedFileName, 'header' => $msgstr];
                } else if ($metaContent != '') {
                    $trailingMeta = ['file' => $truncatedFileName, 'trailingMeta' => $metaContent];
                }
            } else {
                $poArray[$msgid] = [
                    'fr' => '',
                    'en' => '',
                    'context_fr' => '',
                    'context_en' => '',
                    'meta_fr' => '',
                    'meta_en' => '',
                    'file_fr' => '',
                    'file_en' => ''
                ];
                $poArray[$msgid][$lang] = $msgstr;
                $poArray[$msgid][$msgctxtKey] = $msgctxt;
                $poArray[$msgid][$metaContentKey] = $metaContent;
                $poArray[$msgid][$fileKey] = $truncatedFileName;
            }
        }

        return ['poArray' => $poArray, 'header' => $header, 'trailingMeta' => $trailingMeta];
    }

    public function poFilesToArray($poFileNames)
    {
        $poArrays = [];
        $headers = [];
        $trailingMetas = [];

        foreach ($poFileNames as $poFileName) {
            $result = $this->poFileToArray($poFileName);
            $poArrays[] = $result['poArray'];
            $headers[] = $result['header'];
            if (count($result['trailingMeta']) > 0) {
                $trailingMetas[] = $result['trailingMeta'];
            }
        }

        return [
            'poArray' => $this->combinedPoArrays($poArrays),
            'headers' => $headers,
            'trailingMetas' => $trailingMetas
        ];
    }

    public function combinedPoArrays($poArrays)
    {
        $combined = [];

        foreach ($poArrays as $line) {
            foreach ($line as $key => $value) {
                if (!isset($combined[$key])) {
                    $combined[$key] = $value;
                } else {
                    $combined[$key] = $this->arrayCombine($combined[$key], $value);
                }
            }
        }

        return $combined;
    }

    public function poArrayToCsvString($poArray)
    {
        $csvString = "id;fr;en;context fr;context en;meta fr;meta en;file fr;file en\n";

        $poArray = $this->toCsvStrings($poArray);

        foreach ($poArray as $key => $value) {
            $csvString .= $key . $value['fr'] . $value['en'] .
                          $value['context_fr'] . $value['context_en'] .
                          $value['meta_fr'] . $value['meta_en'] .
                          $value['file_fr'] . $value['file_en'] . "\n";
        }

        return $csvString;
    }

    public function headersToCsvString($headers)
    {
        $csvString = "header;file\n";

        $headers = $this->toCsvStrings($headers);

        foreach ($headers as $header) {
            $csvString .= $header['header'] . $header['file'] . "\n";
        }

        return $csvString;
    }

    public function trailingMetasToCsvString($trailingMetas)
    {
        $csvString = "trailing meta;file\n";

        $trailingMetas = $this->toCsvStrings($trailingMetas);

        foreach ($trailingMetas as $trailingMeta) {
            $csvString .= $trailingMeta['trailingMeta'] . $trailingMeta['file'] . "\n";
        }

        return $csvString;
    }

    public function toCsvStrings($array)
    {
        $csvStringArray = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = $this->toCsvStrings($value);
                $key = $this->toCsvString($key);
            } else {
                $value = $this->toCsvString($value);
            }
            $csvStringArray[$key] = $value;
        }

        return $csvStringArray;
    }

    public function toCsvString($string)
    {
        return '"' . str_replace('"', '""', $string) . '";';
    }

    public function fileLang($filePathName)
    {
        if (strpos($filePathName, '/fr/') !== false || strpos($filePathName, '_fr') !== false) {
            return 'fr';
        } else {
            return 'en';
        }
    }

    public function nextMetaContent($fileContent, $contentLength, $pos)
    {
        $metaContent = '';

        $pos = $this->skipSpaces($fileContent, $contentLength, $pos);

        for (; $pos < $contentLength; $pos++) {
            if ($fileContent[$pos] == '#') {
                $result = $this->getLine($fileContent, $contentLength, $pos);
                $pos = $result['newPos'];
                $metaContent .= $result['lineContent'];
            } elseif (!ctype_space($fileContent[$pos])) {
                break;
            }
        }
        return ['metaContent' => $metaContent, 'newPos' => $pos];
    }

    public function nextToken($fileContent, $contentLength, $pos)
    {
        $token = '';

        $pos = $this->skipSpaces($fileContent, $contentLength, $pos);

        for (; $pos < $contentLength; $pos++) {

            if (!ctype_space($fileContent[$pos]) && $fileContent[$pos] != '"') {
                $token .= $fileContent[$pos];
            } else {
                break;
            }
        }

        return ['token' => $token, 'newPos' => $pos];
    }

    public function nextString($fileContent, $contentLength, $pos)
    {
        $msg = '';

        $pos = $this->skipSpaces($fileContent, $contentLength, $pos);

        for (; $pos < $contentLength; $pos++) {
            if ($fileContent[$pos] == '"') {
                for ($pos++; $pos < $contentLength; $pos++) {
                    if ($fileContent[$pos] == '"' && !$this->escapedChar($fileContent, $contentLength, $pos)) {
                        $pos++;
                        if ($this->nextNonSpaceCharIs('"', $fileContent, $contentLength, $pos)) {
                            $result = $this->nextString($fileContent, $contentLength, $pos);
                            $msg = $msg . $result['msg'];
                            $pos = $result['newPos'];
                        }
                        return ['msg' => $msg, 'newPos' => $pos];
                    } else {
                        $msg .= $fileContent[$pos];
                    }
                }
            }
        }

        return ['msg' => $msg, 'newPos' => $pos];
    }

    public function escapedChar($fileContent, $contentLength, $pos)
    {
        if ($pos <= 0) {
            return false;
        } else {
            if ($fileContent[$pos - 1] == '\\') {
                return !$this->escapedChar($fileContent, $contentLength, $pos - 1);
            } else {
                return false;
            }
        }
    }

    public function nextNonSpaceCharIs($char, $fileContent, $contentLength, $pos)
    {
        for (; $pos < $contentLength; $pos++) {
            if (!ctype_space($fileContent[$pos])) {
                if ($fileContent[$pos] != $char) {
                    return false;
                } else {
                    return true;
                }
            }
        }
        return false;
    }

    public function getLine($fileContent, $contentLength, $pos)
    {
        $lineContent = '';
        for (; $pos < $contentLength; $pos++) {
            $lineContent .= $fileContent[$pos];
            if ($fileContent[$pos] == "\n") {
                break;
            }
        }

        return ['lineContent' => $lineContent, 'newPos' => $pos];
    }

    public function skipSpaces($fileContent, $contentLength, $pos)
    {
        for (; $pos < $contentLength; $pos++) {
            if (!ctype_space($fileContent[$pos])) {
                break;
            }
        }

        return $pos;
    }

    public function arrayCombine($array, $array2)
    {
        $combined = [];
        foreach ($array as $key => $value) {
            $combined[$key] = $value == '' ? $array2[$key] : $value;
        }

        return $combined;
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
}
