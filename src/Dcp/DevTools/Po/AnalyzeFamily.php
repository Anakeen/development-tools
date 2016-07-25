<?php

namespace Dcp\DevTools\Po;

use Dcp\DevTools\Template\Exception;

class AnalyzeFamily
{
    protected $enclosure = null;
    protected $delimiter = null;
    protected $outputFiles = [];
    protected $outputDir = null;

    /**
     * @param $outputDir
     * @param string $enclosure the enclosure parameter for the output CSV
     * @param string $delimiter the delimiter parameter for the output CSV
     */
    public function __construct($outputDir, $enclosure = '"', $delimiter = ";")
    {
        $this->outputDir = $outputDir;
        if (!$enclosure) {
            //error_log("The CSV without enclosure is a compatibility mode, you should upgrade your CSV");
        }
        $this->enclosure = $enclosure;
        $this->delimiter = $delimiter;
    }

    /**
     * extractPOFromCSV from a CSV file and print it on standard output
     *
     * @param string $inputFile file input path
     *
     * @throws Exception
     *
     * @return void
     */
    protected function extractPOFromCSV($inputFile)
    {
        $outputDir = $this->outputDir;
        $file = fopen($inputFile, "r");
        if ($file === false) {
            throw new Exception("Unable to read $inputFile");
        }
        $podoc = null;
        $contentToWrite = "";
        $nline = -1;
        $famname = "*******";
        $cv_idview_index = 0;
        $cv_lview_index = 0;
        $cv_menu_index = 0;
        $date = date("c");
        while ($data = $this->getLine($file)) {

            $nline++;

            $num = count($data);
            if ($num < 1) {
                continue;
            }

            $data[0] = trim($this->getArrayIndexValue($data, 0));
            switch ($data[0]) {
                case "BEGIN":
                    $famname = trim($this->getArrayIndexValue($data, 5));
                    $famtitle = trim($this->getArrayIndexValue($data, 2));
                    if ($famname) {
                        $oFileName = $outputDir . DIRECTORY_SEPARATOR . $famname . ".pot";
                        $this->outputFiles[] = $oFileName;
                        $podoc = fopen($oFileName, "w+");
                    } else {
                        $podoc = null;
                    }
                    $contentToWrite = "msgid \"\"\n";
                    $contentToWrite .= "msgstr \"\"\n";
                    $contentToWrite .= "\"Project-Id-Version: $famname \\n\"\n";
                    $contentToWrite .= "\"Report-Msgid-Bugs-To: \\n\"\n";
                    $contentToWrite .= "\"PO-Revision-Date: $date\\n\"\n";
                    $contentToWrite .= "\"Last-Translator: Automatically generated\\n\"\n";
                    $contentToWrite .= "\"Language-Team: none\\n\"\n";
                    $contentToWrite .= "\"MIME-Version: 1.0\\n\"\n";
                    $contentToWrite .= "\"Content-Type: text/plain; charset=UTF-8\\n\"\n";
                    $contentToWrite .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
                    $contentToWrite .= "\"Language: \\n\"\n\n";
                    $contentToWrite .= "#, fuzzy, ($inputFile)\n";
                    $contentToWrite .= "msgid \"" . $famname . "#title\"\n";
                    $contentToWrite .= "msgstr \"" . $this->escapeTranslation($famtitle) . "\"\n\n";

                    break;

                case "END":
                    if (!$podoc) {
                        throw new Exception("Can't create temporary family po file [$outputDir" . DIRECTORY_SEPARATOR . "$famname.pot]");
                    } else {
                        fwrite($podoc, $contentToWrite);
                        fclose($podoc);
                    }
                    $famname = "*******";
                    break;

                case "ORDER":
                    $type = $this->getArrayIndexValue($data, 1);
                    $cv_idview_index = 0;
                    $cv_lview_index = 0;
                    $cv_menu_index = 0;
                    if ($type === "CVDOC") {
                        foreach ($data as $index => $value) {
                            if ($value === "cv_idview") $cv_idview_index = $index;
                            else if ($value === "cv_lview") $cv_lview_index = $index;
                            else if ($value === "cv_menu") $cv_menu_index = $index;
                            if ($cv_idview_index && $cv_lview_index && $cv_menu_index) break;
                        }
                    }
                    break;

                case "DOC":
                    $type = $this->getArrayIndexValue($data, 1);
                    if ($type === "CVDOC") {
                        $cvName = $this->getArrayIndexValue($data, 2);
                        if ($cvName && !is_numeric($cvName) && $cv_idview_index) {
                            $oFileName = $outputDir . DIRECTORY_SEPARATOR . $cvName . ".pot";
                            $cvdoc = fopen($oFileName, "w+");

                            $this->outputFiles[] = $oFileName;
                            if (!$cvdoc) {
                                throw new Exception("fam2po: Can't create tempory CV po file [$outputDir" . DIRECTORY_SEPARATOR . "$cvName.pot]");
                            }
                            $cvContentToWrite = "msgid \"\"\n";
                            $cvContentToWrite .= "msgstr \"\"\n";
                            $cvContentToWrite .= "\"Project-Id-Version: $cvName \\n\"\n";
                            $cvContentToWrite .= "\"Report-Msgid-Bugs-To: \\n\"\n";
                            $cvContentToWrite .= "\"PO-Revision-Date: $date\\n\"\n";
                            $cvContentToWrite .= "\"Last-Translator: Automatically generated\\n\"\n";
                            $cvContentToWrite .= "\"Language-Team: none\\n\"\n";
                            $cvContentToWrite .= "\"MIME-Version: 1.0\\n\"\n";
                            $cvContentToWrite .= "\"Content-Type: text/plain; charset=UTF-8\\n\"\n";
                            $cvContentToWrite .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
                            $cvContentToWrite .= "\"Language: \\n\"\n\n";
                            $tcv_idview = explode('\n', $this->getArrayIndexValue($data, $cv_idview_index));
                            $tcv_lview = explode('\n', $this->getArrayIndexValue($data, $cv_lview_index));
                            $tcv_menu = explode('\n', $this->getArrayIndexValue($data, $cv_menu_index));
                            foreach ($tcv_idview as $i => $id) {
                                if ($cv_lview_index && $tcv_lview[$i]) {
                                    $cvContentToWrite .= "#: $inputFile\n";
                                    $cvContentToWrite .= "#, fuzzy\n";
                                    $cvContentToWrite .= "msgid \"" . $this->escapeId($cvName) . "#label#" . $this->escapeId($id) . "\"\n";
                                    $cvContentToWrite .= "msgstr \"" . $this->escapeTranslation($tcv_lview[$i]) . "\"\n\n";
                                }
                                if ($cv_menu_index && $tcv_menu[$i]) {
                                    $cvContentToWrite .= "#: $inputFile\n";
                                    $cvContentToWrite .= "#, fuzzy\n";
                                    $cvContentToWrite .= "msgid \"" . $this->escapeId($cvName) . "#menu#" . $this->escapeId($id) . "\"\n";
                                    $cvContentToWrite .= "msgstr \"" . $this->escapeTranslation($tcv_menu[$i]) . "\"\n\n";
                                }
                            }
                            fwrite($cvdoc, $cvContentToWrite);
                            fclose($cvdoc);
                        }
                    }
                    break;

                case "ATTR":
                case "MODATTR":
                case "PARAM":
                case "OPTION":
                    $contentToWrite .= "#: $inputFile\n";
                    $contentToWrite .= "#, fuzzy, ($inputFile)\n";
                    $contentToWrite .= "msgid \"" . $famname . "#" . strtolower($this->getArrayIndexValue($data, 1)) . "\"\n";
                    $contentToWrite .= "msgstr \"" . $this->escapeTranslation($this->getArrayIndexValue($data, 3)) . "\"\n\n";
                    // Enum ----------------------------------------------
                    $type = $this->getArrayIndexValue($data, 6);
                    if ($type == "enum" || $type == "enumlist") {
                        $phpFile = str_replace('\,', '\#', $this->getArrayIndexValue($data, 11));
                        if (!$phpFile) {
                            $enumDefinition = str_replace('\,', '\#', $this->getArrayIndexValue($data, 12));
                            $tenum = explode(",", $enumDefinition);
                            foreach ($tenum as $ve) {
                                $enumDefinition = str_replace('\#', ',', $ve);
                                $enumValues = explode("|", $enumDefinition);
                                $contentToWrite .= "#, fuzzy, ($inputFile)\n";
                                $contentToWrite .= "msgid \"" . $famname . "#" . strtolower($this->getArrayIndexValue($data, 1)) . "#" . (str_replace('\\', '', $this->getArrayIndexValue($enumValues, 0))) . "\"\n";
                                $contentToWrite .= "msgstr \"" . $this->escapeTranslation((str_replace('\\', '', $this->getArrayIndexValue($enumValues, 1)))) . "\"\n\n";
                            }
                        }
                    }
                    // Options ----------------------------------------------
                    $options = $this->getArrayIndexValue($data, 15);
                    $options = explode("|", $options);
                    foreach ($options as $currentOption) {
                        $currentOption = explode("=", $currentOption);
                        $currentOptionKey = $this->getArrayIndexValue($currentOption, 0);
                        $currentOptionValue = $this->getArrayIndexValue($currentOption, 1);
                        switch (strtolower($currentOptionKey)) {
                            case "elabel":
                            case "ititle":
                            case "submenu":
                            case "ltitle":
                            case "eltitle":
                            case "elsymbol":
                            case "lsymbol":
                            case "showempty":
                                $contentToWrite .= "#, fuzzy, ($inputFile)\n";
                                $contentToWrite .= "msgid \"" . $famname . "#" . strtolower($this->getArrayIndexValue($data, 1)) . "#" . strtolower($currentOptionKey) . "\"\n";
                                $contentToWrite .= "msgstr \"" . $this->escapeTranslation($currentOptionValue) . "\"\n\n";
                        }
                    }
            }
        }
    }

    protected function getLine(&$file)
    {
        if ($this->enclosure) {
            return fgetcsv($file, null, $this->delimiter, $this->enclosure);
        } else {
            $line = fgets($file);
            if ($line) {
                $line = explode($this->delimiter, $line);
            }
            return $line;
        }
    }

    protected function getArrayIndexValue(&$array, $index)
    {
        return isset($array[$index]) ? $array[$index] : "";
    }

    public function extract($filesPath)
    {
        foreach ($filesPath as $currentPath) {
            $this->extractPOFromCSV($currentPath);
        }
    }

    public function escapeTranslation($key)
    {
        $key =  str_replace('"', '\"', $key);
        return str_replace("\n", '\n', $key);
    }

    public function escapeId($key)
    {
        return str_replace("\n", '\n', $key);
    }


} 