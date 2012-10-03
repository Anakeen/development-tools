<?php

/**
 * extract strings from family file (csv or ods) and generate associated po catalog
 *
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package DCP
 * @subpackage devtools
 */
define ("SEPCHAR", ';');
define ("ALTSEPCHAR", ' --- ');

$inrow = false;
$incell = false;
$nrow = 0;
$ncol = 0;
$rows = array();
$colrepeat = 0;

for ($i = 1; $i < count($argv); $i++) {
    debugPrompt("Processing file " . $argv[$i]);
    $pf = pathinfo($argv[$i]);
    if (file_exists($argv[$i])) {
        if (isset($pf['extension'])) {
            switch ($pf['extension']) {
                case 'ods':
                    debugPrompt("  --- csv extraction");
                    $csvfile = $argv[$i] . ".csv";
                    ods2csv($argv[$i], $csvfile);

                    if ($csvfile) {
                        makePo($csvfile);
                    }
                    unlink($csvfile);
                    break;
                case 'csv':
                    makePo($argv[$i]);
                    break;
                default:
                    debugPrompt($argv[$i] . " has an unknown extension, skipping it.");
            }
        } else {
            debugPrompt($argv[$i] . " has no extension, skipping it.");
        }
    } else {
        debugPrompt("Can't access file " . $argv[$i]);
    }
}

function startElement($parser, $name, $attrs)
{
    global $rows, $nrow, $inrow, $incell, $ncol, $colrepeat, $celldata;
    if ($name == "TABLE:TABLE-ROW") {
        $inrow = true;
        if (isset($rows[$nrow])) {
            // fill empty cells
            $idx = 0;
            foreach ($rows[$nrow] as $k=> $v) {
                if (!isset($rows[$nrow][$idx])) $rows[$nrow][$idx] = '';
                $idx++;
            }
            ksort($rows[$nrow], SORT_NUMERIC);
        }
        $nrow++;
        $ncol = 0;
        $rows[$nrow] = array();
    }

    if ($name == "TABLE:TABLE-CELL") {
        $incell = true;
        $celldata = "";
        if ($attrs["TABLE:NUMBER-COLUMNS-REPEATED"]) {
            $colrepeat = intval($attrs["TABLE:NUMBER-COLUMNS-REPEATED"]);
        }
    }
    if ($name == "TEXT:P") {
        if (strlen($rows[$nrow][$ncol]) > 0) $rows[$nrow][$ncol] .= '\n';
    }
}

function endElement($parser, $name)
{
    global $rows, $nrow, $inrow, $incell, $ncol, $colrepeat, $celldata;
    if ($name == "TABLE:TABLE-ROW") {
        // Remove trailing empty cells
        $i = $ncol - 1;
        while ($i >= 0) {
            if (strlen($rows[$nrow][$i]) > 0) {
                break;
            }
            $i--;
        }
        array_splice($rows[$nrow], $i + 1);
        $inrow = false;
    }

    if ($name == "TABLE:TABLE-CELL") {
        $incell = false;

        $rows[$nrow][$ncol] = $celldata;

        if ($colrepeat > 1) {
            $rval = $rows[$nrow][$ncol];
            for ($i = 1; $i < $colrepeat; $i++) {
                $ncol++;
                $rows[$nrow][$ncol] = $rval;
            }
        }
        //$ncol+=intval($attrs["TABLE:NUMBER-COLUMNS-REPEATED"]);

        $ncol++;
        $colrepeat = 0;
    }
}

function characterData($parser, $data)
{
    global $rows, $nrow, $inrow, $incell, $ncol, $celldata;
    if ($inrow && $incell) {
        $celldata .= preg_replace(
            '/^\s*[\r\n]\s*$/ms', '', str_replace(SEPCHAR, ALTSEPCHAR, $data)
        );
    }
    //  print $data. "- ";
}

function xmlcontent2csv($xmlcontent, &$fcsv)
{
    $err = "";
    global $rows;
    $xml_parser = xml_parser_create();
    // Utilisons la gestion de casse, de maniere a etre surs de trouver la balise dans $map_array
    xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, true);
    xml_parser_set_option($xml_parser, XML_OPTION_SKIP_WHITE, 0);
    xml_set_element_handler($xml_parser, "startElement", "endElement");
    xml_set_character_data_handler($xml_parser, "characterData");

    if (!xml_parse($xml_parser, $xmlcontent)) {
        $err = sprintf("error XML : %s line %d",
            xml_error_string(xml_get_error_code($xml_parser)),
            xml_get_current_line_number($xml_parser)
        );
    } else {
        xml_parser_free($xml_parser);
        //print_r($rows);
        foreach ($rows as $k=> $row) {
            $fcsv .= implode(SEPCHAR, $row) . "\n";
        }
    }
    return $err;
}

function ods2content($odsfile, &$content)
{
    $err = "";
    if (file_exists($odsfile)) {
        $cibledir = uniqid("/var/tmp/ods");

        $cmd = sprintf("unzip -j %s content.xml -d %s >/dev/null", $odsfile, $cibledir);
        system($cmd);

        $contentxml = $cibledir . "/content.xml";
        if (file_exists($contentxml)) {
            $content = file_get_contents($contentxml);
            unlink($contentxml);
        }

        rmdir($cibledir);
    } else {
        $err = "file $odsfile not found";
    }
    return $err;
}

function ods2csv($odsfile, $csvfile)
{
    if ($odsfile == "") {
        print "odsfile needed :usage  --odsfile=<ods file> [--csvfile=<csv file output>]\n";
        return;
    }

    $err = ods2content($odsfile, $content);
    if ($err == "") {
        $err = xmlcontent2csv($content, $csv);
        if ($err == "") {
            if ($csvfile) {
                $n = file_put_contents($csvfile, $csv);
                //if ($n > 0) print sprintf(_("csv file <%s> wroted")."\n",$csvfile);
                //else $err=sprintf(_("cannot write  %s"),$csvfile);
            } else print $csv;
        }
    }
    if ($err != "") print "ERROR:$err\n";

}

function debugPrompt($msg)
{
    error_log("fam2po: " . $msg);
}

function makePo($fi)
{

    $fdoc = fopen($fi, "r");
    if (!$fdoc)
        debugPrompt("fam2po: Can't access file [$fi]");
    $nline = -1;
    $famname = "*******";
    $famtitle = "";

    while (!feof($fdoc)) {

        $nline++;

        $buffer = rtrim(fgets($fdoc, 16384));
        $data = explode(";", $buffer);

        // if (isUTF8($data))    $data=array_map("utf8_decode",$data);

        $num = count($data);
        if ($num < 1) continue;

        $data[0] = trim($data[0]);
        switch ($data[0]) {

            case "BEGIN":
                $famname = $data[5];
                $famtitle = $data[2];

                echo "#, fuzzy, ($fi::$nline)\n";
                echo "msgid \"" . $famname . "#title\"\n";
                echo "msgstr \"" . $famtitle . "\"\n\n";
                break;

            case "END":
                $famname = "*******";
                $famtitle = "";
                break;

            case "ATTR":
            case "MODATTR":
            case "PARAM":
            case "OPTION":
                echo "#. " . $data[3] . "\n";
                echo "#, fuzzy, ($fi::$nline)\n";
                echo "msgid \"" . $famname . "#" . strtolower($data[1]) . "\"\n";
                echo "msgstr \"" . $data[3] . "\"\n\n";

                // Enum ----------------------------------------------
                if ($data[6] == "enum" || $data[6] == "enumlist") {
                    $d = str_replace('\,', '\#', $data[12]);
                    $tenum = explode(",", $d);
                    foreach ($tenum as $ke=> $ve) {
                        $d = str_replace('\#', ',', $ve);
                        $ee = explode("|", $d);
                        echo "#. " . $ee[1] . "\n";
                        echo "#, fuzzy, ($fi::$nline)\n";
                        echo "msgid \"" . $famname . "#" . strtolower($data[1]) . "#" . (str_replace('\\', '', $ee[0])) . "\"\n";
                        echo "msgstr \"" . (str_replace('\\', '', $ee[1])) . "\"\n\n";
                    }
                }

                // Options ----------------------------------------------
                $topt = explode("|", $data[15]);
                foreach ($topt as $ko=> $vo) {
                    $oo = explode("=", $vo);
                    switch (strtolower($oo[0])) {

                        case "elabel" :
                        case "ititle" :
                        case "submenu" :
                        case "ltitle" :
                        case "eltitle" :
                        case "elsymbol" :
                        case "showempty" :
                            echo "#. " . $oo[1] . "\n";
                            echo "#, fuzzy, ($fi::$nline)\n";
                            echo "msgid \"" . $famname . "#" . strtolower($data[1]) . "#" . strtolower($oo[0]) . "\"\n";
                            echo "msgstr \"" . $oo[1] . "\"\n\n";
                            break;

                    }
                }

                break;
        }

    }
}


?>
