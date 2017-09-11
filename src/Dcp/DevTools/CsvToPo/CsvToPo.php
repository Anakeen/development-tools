<?php

namespace Dcp\DevTools\CsvToPo;

/**
 * Class CsvToPo
 * @package Dcp\DevTools\CsvToPo
 */
class CsvToPo
{
    protected $options;

    /**
     * CsvToPo constructor.
     *
     * @param array $options
     *
     * @throws Exception
     */
    public function __construct(array $options = [])
    {
        $missingOptions = [];

        if (!isset($options['source'])) {
            $missingOptions['source']
                = 'You need to set the path of the .csv file to convert into .po files with -s or --source';
        }

        if (!isset($options['output'])) {
            $missingOptions['output']
                = 'You need to set the path of the directory where to put the .po files with -o or --output';
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
        $this->options['sourcePath'] = $this->options['source'];
        $this->options['outputPath'] = realpath($this->options['output']);
    }

    /**
     * Convert the .csv file specified by $this->options['sourcePath']
     * in at least one .po file that will be put in the
     * directory specified by $this->options['outputPath'].
     *
     * @return string[] Logs specifying what files have been converted.
     * @throws Exception
     */
    public function convert()
    {
        $csvParser = new CsvParser($this->options['sourcePath']);
        $mergedPo = $csvParser->parse();

        $poUnMerger = new PoUnMerger($mergedPo);
        $poFiles = $poUnMerger->unMerge();

        foreach ($poFiles as $poFile) {
            $fileName = $this->options['outputPath'] . substr($poFile->fileName, 1, -1);

            $fileSuccessfullyCreated = $this->createFileAndPath($fileName);
            if ($fileSuccessfullyCreated) {
                $csvString = $this->poFileToPoString($poFile);
                file_put_contents(
                    $fileName,
                    $csvString
                );
            } else {
                throw new Exception(
                    sprintf('Cannot create the file %s', $fileName)
                );
            }
        }

        return [
            'convertedPoFiles' => array_map(
                function ($p) {
                    return $this->options['outputPath'] . substr($p->fileName, 1, -1);
                },
                $poFiles
            )
        ];
    }

    /**
     * Convert a PoFile Object to a string
     * and return it. This string is the content
     * that should be writen in the .po file.
     *
     * @param \Dcp\DevTools\PoToCsv\PoFile $poFile
     *
     * @return string
     */
    public function poFileToPoString($poFile)
    {
        $poString = '';

        if ($poFile->headerMeta !== null) {
            $poString .= substr($poFile->headerMeta, 1, -1) . "\n\n";
        }

        $poString .= "msgid \"\"\nmsgstr " .
            $this->toMultiLineString($poFile->header) . "\n\n";

        foreach ($poFile->poElements as $poElement) {
            $poElement->id = $this->toMultiLineString($poElement->id);
            $poElement->message = $this->toMultiLineString($poElement->message);

            if ($poElement->meta != '') {
                $poString .= substr($poElement->meta, 1, -1) . "\n";
            }
            if ($poElement->context != null) {
                $poString .= "msgctxt " .
                    $this->toMultiLineString($poElement->context) . "\n";
                $poElement->id = $this->removeContextFromId($poElement->id);
            }
            $poString .= "msgid " .
                $poElement->id . "\n" . "msgstr " .  $poElement->message . "\n\n";
        }

        if ($poFile->trailingMeta !== null && $poFile->trailingMeta != '') {
            $poString .= substr($poFile->trailingMeta, 1, -1);
        }

        return $poString;
    }

    /**
     * Convert a string (or null) to a format that
     * make sense for a .po file.
     *
     * @param string|null $string
     *
     * @return string
     */
    public function toMultiLineString($string)
    {
        if ($string === null || $string === '') {
            return '""';
        }

        $string = str_replace("\n", "\"\n\"", $string);

        if (isset($string[0]) && $string[0] == "\n") {
            $string = "\"\"" . $string;
        }

        return $string;
    }

    /**
     * Remove the last parenthesis block of a string
     * and the content after it, and add a " (double quote)
     * at the end. Return it.
     * The aim is to remove the context that have
     * been added to an id to make it unique.
     * E.g: "Aucune action (afnorapp)" => "Aucune action"
     *
     * @param string $string
     *
     * @return string string
     */
    public function removeContextFromId($string)
    {
        $rigthParensAmount = 0;
        $stringLength = strlen($string);

        for ($i = $stringLength - 1; $i >= 0; $i--) {
            if ($string[$i] == ')') {
                $rigthParensAmount++;
            } elseif ($string[$i] == '(') {
                $rigthParensAmount--;
                if ($rigthParensAmount == 0) {
                    $i--;
                    break;
                }
            }
        }

        return substr($string, 0, $i) . '"';
    }

    /**
     * Create the directoties path $path,
     * only if they don't already exist.
     *
     * @param string $path
     *
     * @return bool true if the directories all created
     * successfully or already existed, false otherwise.
     */
    public function createPath($path)
    {
        $success = true;

        if ($path != '/') {
            $success = $this->createPath(dirname($path));
        }
        if ($success && !is_dir($path)) {
            return mkdir($path);
        }

        return $success;
    }

    /**
     * Create the file path name $fileName,
     * create all directory needed to create this
     * file, and create it.
     *
     * @param string $fileName
     *
     * @return bool true if the file successfully created,
     * false otherwise.
     */
    public function createFileAndPath($fileName)
    {
        $success = $this->createPath(dirname($fileName));

        if ($success) {
            return touch($fileName);
        }

        return $success;
    }
}
