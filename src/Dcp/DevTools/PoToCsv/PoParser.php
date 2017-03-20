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

    public function __construct($fileName)
    {
        $this->fileName = $fileName;
        $this->fileContent = file_get_contents($fileName);
        $this->contentLength = strlen($this->fileContent);
        $this->pos = 0;
    }

    public function currentChar()
    {
        return $this->fileContent[$this->pos];
    }

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

            $msgstr = $this->nextToken();
            $number = $this->digitsIn($msgstr);
            if ($number != '') {
                $poElement->id .= " [ for " . $number . " elements ]";
            }

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

    public function digitsIn($string)
    {
        $number = '';

        for ($i = 0; $i < strlen($string); $i++) {
            if (is_numeric($string[$i])) {
                $number .= $string[$i];
            }
        }

        return $number;
    }

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

    public function skipSpaces()
    {
        for (; $this->pos < $this->contentLength; $this->pos++) {
            if (!ctype_space($this->currentChar())) {
                break;
            }
        }
    }

    public function fileLang($filePathName)
    {
        if (strpos($filePathName, '/fr/') !== false || strpos($filePathName, '_fr') !== false) {
            return 'fr';
        } else {
            return 'en';
        }
    }
}
