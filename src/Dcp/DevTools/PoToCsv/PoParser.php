<?php


namespace Dcp\DevTools\PoToCsv;

/**
 * Class PoParser
 * @package Dcp\DevTools\PoToCsv
 */
class PoParser
{
    public $fileName;
    public $fileContent;
    public $contentLength;
    public $pos;

    /**
     * PoParser constructor.
     *
     * @param string $fileName.
     */
    public function __construct($fileName)
    {
        $this->fileName = $fileName;
        $this->fileContent = file_get_contents($fileName);
        $this->contentLength = strlen($this->fileContent);
        $this->pos = 0;
    }

    /**
     * Return the current character being parsed.
     *
     * @return string.
     */
    public function currentChar()
    {
        return $this->fileContent[$this->pos];
    }

    /**
     * Parse the .po file $this->fileName to create a PoFile object
     * and return it.
     *
     * TODO: Handle plural form,
     * see http://pology.nedohodnik.net/doc/user/en_US/ch-poformat.html.
     *
     * @return PoFile.
     */
    public function parse()
    {
        $poFile = new PoFile($this->fileName, $this->fileLang($this->fileName));

        while ($this->pos < $this->contentLength) {
            $poElement = new PoElement();

            $poElement->meta = $this->nextMetaContent();

            $token = $this->nextToken();

            if ($token == 'msgctxt') {
                $poElement->context = $this->nextString();
                $this->nextToken();
            }

            $poElement->id = $this->nextString();

            $this->nextToken();

            $poElement->message = $this->nextString();

            if ($poElement->context != null) {
                $poElement->id .= " (" . $poElement->context . ")";
            }

            $poFile->poElements[] = $poElement;
        }

        $firstElem = array_shift($poFile->poElements);
        $poFile->header = $firstElem->message;
        $poFile->headerMeta = $firstElem->meta;

        if (end($poFile->poElements)->id == '') {
            $poFile->trailingMeta = array_pop($poFile->poElements);
            $poFile->trailingMeta = $poFile->trailingMeta->meta;
        }

        return $poFile;
    }

    /**
     * Find the next comments (group of lines beginning with '#')
     * and return it.
     *
     * @return string.
     */
    public function nextMetaContent()
    {
        $metaContent = '';

        $this->skipSpaces();

        for (; $this->pos < $this->contentLength; $this->pos++) {
            if ($this->currentChar() == '#') {
                $metaContent .= $this->getLine();
            } elseif (!ctype_space($this->currentChar())) {
                break;
            }
        }
        // Remove the \n at the end
        return trim($metaContent);
    }

    /**
     * Find the next token and return it,
     * a token is considered a group of character without space or '"' in it.
     *
     * @return string.
     */
    public function nextToken()
    {
        $token = '';

        $this->skipSpaces();

        for (; $this->pos < $this->contentLength; $this->pos++) {
            if (!ctype_space($this->currentChar()) && $this->currentChar() != '"') {
                $token .= $this->currentChar();
            } else {
                break;
            }
        }

        return $token;
    }

    /**
     * Find the next group of string and return it,
     * as a concatenated string.
     * A group of string is 1 or more strings,
     * one after the other, they can be separated by spaces, line jumps...
     * These spaces will be concatenated with the strings in order to
     * keep the original layout.
     *
     * @return string.
     */
    public function nextString()
    {
        $msg = '';

        $this->skipSpaces();

        for (; $this->pos < $this->contentLength; $this->pos++) {
            if ($this->currentChar() == '"') {
                $this->pos++;
                for (; $this->pos < $this->contentLength; $this->pos++) {
                    if ($this->currentChar() == '"' && !$this->isEscapedChar($this->pos)) {
                        $this->pos++;
                        if ($this->nextNonSpaceCharIs('"')) {
                            $msg .= "\n" . $this->nextString();
                        }
                        return $msg;
                    } else {
                        $msg .= $this->currentChar();
                    }
                }
            }
        }

        return $msg;
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
     * Reurn true if the next non-space character is
     * $char, false otherwise.
     *
     * @param string $char The character to look for.
     *
     * @return bool.
     */
    public function nextNonSpaceCharIs($char)
    {
        for (; $this->pos < $this->contentLength; $this->pos++) {
            if (!ctype_space($this->currentChar())) {
                if ($this->currentChar() != $char) {
                    return false;
                } else {
                    return true;
                }
            }
        }
        return false;
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
                break;
            }
        }

        return $lineContent;
    }

    /**
     * Move the parser position to the next non-space
     * character.
     */
    public function skipSpaces()
    {
        for (; $this->pos < $this->contentLength; $this->pos++) {
            if (!ctype_space($this->currentChar())) {
                break;
            }
        }
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
