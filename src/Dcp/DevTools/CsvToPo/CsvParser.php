<?php

namespace Dcp\DevTools\CsvToPo;

use Dcp\DevTools\PoToCsv\MergedPo;
use Dcp\DevTools\PoToCsv\MergedPoElement;
use Dcp\DevTools\PoToCsv\PoElement;
use Dcp\DevTools\PoToCsv\PoFile;
use Dcp\DevTools\PoToCsv\MetaInfo;

/**
 * Class CsvParser
 *
 * @package Dcp\DevTools\CsvToPo
 */
class CsvParser
{
    public $fileName;
    public $fileContent;
    public $contentLength;
    public $pos;

    /**
     * CsvParser constructor.
     *
     * @param string $fileName The name of the .csv file to parse.
     */
    public function __construct($fileName)
    {
        $this->fileName = $fileName;
        $this->fileContent = file_get_contents($fileName);
        $this->contentLength = strlen($this->fileContent);
        $this->pos = 0;
    }

    /**
     * @return string The character at the current parser position.
     */
    public function currentChar()
    {
        return $this->fileContent[$this->pos];
    }

    /**
     * Transform the .csv file of the parser into
     * a MergedPo Object, and return it.
     *
     * @return MergedPo
     */
    public function parse()
    {
        $mergedPo = new MergedPo($this->langs());

        $cell = $this->nextCellContent();
        while ($this->pos < $this->contentLength && ($cell === null || $cell[0] == '"')) {
            $mergedPoElement = new MergedPoElement(new PoElement(), '', '', []);
            $mergedPoElement->id = $cell;

            foreach ($mergedPo->langs as $lang) {
                $mergedPoElement->messages[$lang] = $this->nextCellContent();
            }
            foreach ($mergedPo->langs as $lang) {
                $mergedPoElement->contexts[$lang] = $this->nextCellContent();
            }
            foreach ($mergedPo->langs as $lang) {
                $mergedPoElement->metas[$lang] = $this->nextCellContent();
            }
            foreach ($mergedPo->langs as $lang) {
                $mergedPoElement->fileNames[$lang] = $this->nextCellContent();
            }

            $mergedPo->mergedPoElements[] = $mergedPoElement;

            // Skip line jumps
            $cell = $this->nextCellContent();
            while ($cell === '') {
                $cell = $this->nextCellContent();
            }
        }

        $this->getLine();// Skip headers
        while ($this->pos < $this->contentLength) {
            $metaInfo = new MetaInfo(new PoFile('', ''));
            $metaInfo->fileName = $this->nextCellContent();
            $metaInfo->header = $this->nextCellContent();
            $metaInfo->headerMeta = $this->nextCellContent();
            $metaInfo->trailingMeta = $this->nextCellContent();

            $mergedPo->metaInfos[] = $metaInfo;

            $this->nextCellContent(); // Skip line jump
        }

        return $mergedPo;
    }

    /**
     * (Assume the cell separator is ; (semicolon) and the string
     * container is " (double quote)).
     * Find the next content of the next .csv cell
     * inside the parsed file and return it.
     * If the cell is completely empty (;;) returns an
     * empty string, if the cell contains and empty string
     * (;"";) returns null.
     * Otherwise the content is returned, strings between
     * double quotes will be returned with the double quotes.
     *
     * @return null|string
     */
    public function nextCellContent()
    {
        $isInString = false;
        $cellContent = '';

        for (; $this->pos < $this->contentLength; $this->pos++) {
            if ($this->currentChar() == '"') {
                $isInString = !$isInString;
                if (!$isInString) {
                    continue;
                }
            }
            if (!$isInString &&
                ($this->currentChar() == ';' || $this->currentChar() == "\n")) {
                $this->pos++;
                break;
            }
            $cellContent .= $this->currentChar();
        }

        if (isset($cellContent[0]) && $cellContent[0] == '"') {
            $cellContent .= '"';
        }

        return $cellContent == '""' ? null : $cellContent;
    }

    /**
     * Parse the first line of the .csv file to return
     * the language country codes in it.
     *
     * @return string[] An array containing language country codes.
     */
    public function langs()
    {
        $this->pos = 0;
        $langs = [];

        $headers = explode(';', $this->getLine());

        for ($i = 1; $i <  count($headers); $i++) {
            if ($this->stringContainsSpace($headers[$i])) {
                break;
            } else {
                $langs[] = $headers[$i];
            }
        }

        return $langs;
    }

    /**
     * Convert a csvString to a normal string
     * (removing the outer double quotes) and return it
     * E.g: "L audit est ""annulé""" => L'audit est "annulé".
     *
     * @param string $csvString
     *
     * @return string
     */
    public function csvStringToString($csvString)
    {
        return str_replace('""', '"', substr($csvString, 1, -1));
    }

    /**
     * Return the content of the file from the current
     * position to the next line jump (line jump included).
     *
     * @return string.
     */
    public function getLine()
    {
        $lineContent = '';

        for (; $this->pos < $this->contentLength; $this->pos++) {
            $lineContent .= $this->currentChar();
            if ($this->currentChar() == "\n") {
                $this->pos++;
                break;
            }
        }

        return $lineContent;
    }

    /**
     * Return true if the character located at the
     * position $tempPos in the file being parsed is
     * escaped by back slashes, false otherwise.
     * E.g: \n is escaped \\n is not escaped.
     *
     * @param integer $tempPos The position of the character to check
     * in the file.
     *
     * @return bool.
     */
    public function isEscapedChar($tempPos)
    {
        if ($tempPos <= 0) {
            return false;
        } else {
            if ($this->fileContent[$tempPos - 1] == '\\') {
                return !$this->isEscapedChar($tempPos - 1);
            } else {
                return false;
            }
        }
    }

    /**
     * Return true if the string contains at least one space
     * false otherwise.
     *
     * @param string $string
     *
     * @return bool
     */
    public function stringContainsSpace($string)
    {
        for ($i = 0; $i < strlen($string); $i++) {
            if (ctype_space($string[$i])) {
                return true;
            }
        }
        return false;
    }
}
