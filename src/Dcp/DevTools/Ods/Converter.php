<?php

namespace Dcp\DevTools\Ods;

class Converter
{

    protected $enclosure = null;
    protected $delimiter = null;

    private $rows = array();
    private $nrows = 0;
    private $ncol = 0;
    private $celldata = '';
    private $colrepeat = false;
    private $inrow = false;
    private $incell = false;
    private $cellattrs = array();

    /**
     * @param string $enclosure the enclosure parameter for the output CSV
     * @param string $delimiter the delimiter parameter for the output CSV
     */
    public function __construct($enclosure = '"', $delimiter = ";")
    {
        $this->enclosure = $enclosure;
        $this->delimiter = $delimiter;
    }

    /**
     * Take an ODS file and produce one CSV
     *
     * @param string $inputFile path to an ODS file
     * @param string $outputFile path where a writeable place
     * @param boolean $force force the write if the output file exist
     *
     * @throws ConverterException
     * @return Converter
     */
    public function convert($inputFile, $outputFile, $force = false)
    {
        if (!file_exists($inputFile)) {
            throw new ConverterException("Unable to find the ODS file at $inputFile");
        }

        if (file_exists($outputFile) && $force === false) {
            throw new ConverterException("There is a file at $outputFile. Use force to overwrite it.");
        }

        $this->reinitInternalElements();

        $content = $this->unzipODS($inputFile);
        $this->parseContent($content);

        $this->writeCSVFile($outputFile);

        return $this;
    }

    /**
     * Write the CSV
     *
     * @param string $outputFile path to the output file
     * @throws ConverterException
     */
    protected function writeCSVFile($outputFile) {
        $outputFile = fopen($outputFile, "w");
        if ($outputFile === false) {
            throw new ConverterException("Unable to open $outputFile in w mode");
        }
        foreach($this->rows as $currentRow) {
            fputcsv($outputFile, $currentRow, $this->delimiter, $this->enclosure);
        }
        fclose($outputFile);
    }

    /**
     * Reinit the internal elements
     *
     * @return $this
     */
    protected function reinitInternalElements()
    {
        $this->rows = array();
        $this->nrows = 0;
        $this->ncol = 0;
        $this->colrepeat = false;
        $this->inrow = false;
        $this->incell = false;
        return $this;
    }

    /**
     * Extract content from an ods file
     *
     * @param  string $odsfile file path
     * @throws ConverterException
     * @return string
     */
    protected function unzipODS($odsfile)
    {
        $contentFile = fopen("zip://$odsfile#content.xml", "r");
        if ($contentFile === false) {
            throw new ConverterException("Unable to read the content.xml of $odsfile");
        }
        $content = "";
        while (!feof($contentFile)) {
            $content .= fread($contentFile, 2);
        }
        fclose($contentFile);
        return $content;
    }

    /**
     * @param $xmlcontent
     *
     * @throws ConverterException
     * @return string
     */
    protected function parseContent($xmlcontent)
    {

        $xml_parser = xml_parser_create();
        xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, true);
        xml_parser_set_option($xml_parser, XML_OPTION_SKIP_WHITE, 0);
        xml_set_object($xml_parser, $this);
        xml_set_element_handler($xml_parser, "startElement", "endElement");
        xml_set_character_data_handler($xml_parser, "characterData");

        if (!xml_parse($xml_parser, $xmlcontent)) {
            throw new ConverterException(sprintf("Unable to parse XML : %s line %d", xml_error_string(xml_get_error_code($xml_parser)), xml_get_current_line_number($xml_parser)));
        }
        xml_parser_free($xml_parser);
    }

    /* Handling method for XML parser*/
    protected function startElement(
        /** @noinspection PhpUnusedParameterInspection */
        $parser, $name, $attrs)
    {

        if ($name == "TABLE:TABLE-ROW") {
            $this->inrow = true;
            if (isset($this->rows[$this->nrows])) {
                // fill empty cells
                $idx = 0;
                /** @noinspection PhpUnusedLocalVariableInspection */
                foreach ($this->rows[$this->nrows] as & $v) {
                    if (!isset($this->rows[$this->nrows][$idx])) {
                        $this->rows[$this->nrows][$idx] = '';
                    }
                    $idx++;
                }
                ksort($this->rows[$this->nrows], SORT_NUMERIC);
            }
            $this->nrows++;
            $this->ncol = 0;
            $this->rows[$this->nrows] = array();
        }

        if ($name == "TABLE:TABLE-CELL") {
            $this->incell = true;
            $this->celldata = "";
            $this->cellattrs = $attrs;
            if (!empty($attrs["TABLE:NUMBER-COLUMNS-REPEATED"])) {
                $this->colrepeat = intval($attrs["TABLE:NUMBER-COLUMNS-REPEATED"]);
            }
        }
        if ($name == "TEXT:P") {
            if (isset($this->rows[$this->nrows][$this->ncol])) {
                if (strlen($this->rows[$this->nrows][$this->ncol]) > 0) {
                    $this->rows[$this->nrows][$this->ncol] .= '\n';
                }
            }
        }
    }

    protected function endElement($parser, $name)
    {

        if ($name == "TABLE:TABLE-ROW") {
            // Remove trailing empty cells
            $i = $this->ncol - 1;
            while ($i >= 0) {
                if (strlen($this->rows[$this->nrows][$i]) > 0) {
                    break;
                }
                $i--;
            }
            array_splice($this->rows[$this->nrows], $i + 1);
            $this->inrow = false;
        }
        if ($name == "TEXT:S") {
            $this->celldata .= ' ';
        }
        if ($name == "TABLE:TABLE-CELL") {
            $this->incell = false;

            if ($this->celldata === '') {
                $this->celldata = $this->getOfficeTypedValue($this->cellattrs);
            }

            $this->rows[$this->nrows][$this->ncol] = $this->celldata;

            if ($this->colrepeat > 1) {
                $rval = $this->rows[$this->nrows][$this->ncol];
                for ($i = 1; $i < $this->colrepeat; $i++) {
                    $this->ncol++;
                    $this->rows[$this->nrows][$this->ncol] = $rval;
                }
            }
            $this->ncol++;
            $this->colrepeat = 0;
        }
    }

    protected function characterData($parser, $data)
    {

        if ($this->inrow && $this->incell) {
            $this->celldata .= $data;
        }
    }

    private function getOfficeTypedValue($attrs)
    {
        $value = '';
        /* Get value from property OFFICE:<type>-VALUE */
        if (isset($attrs['OFFICE:VALUE-TYPE'])) {
            $type = strtoupper($attrs['OFFICE:VALUE-TYPE']);
            $propName = 'OFFICE:' . $type . '-VALUE';
            if (isset($attrs[$propName])) {
                $value = (string)$attrs[$propName];
            }
        }
        /* Get value from property OFFICE:VALUE */
        if ($value == '' && isset($attrs['OFFICE:VALUE'])) {
            $value = (string)$attrs['OFFICE:VALUE'];
        }
        return $value;
    }
} 