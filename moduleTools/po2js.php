<?php
/**
 * translate mo file to je file obejct
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 */

Class po2js {
    public $pofile="";
    public $entry=array();
    private $encoding='utf-8';
    function __construct($pofile) {
        $this->pofile=$pofile;
    }
    public function parseEntry(&$out) {
        $out = preg_replace(
			"/(?m)\[BLOCK\s*([^\]]*)\](.*?)\[ENDBLOCK\s*\\1\]/se", 
			"\$this->memoEntry('\\1','\\2')",
        $out);
    }
    public static function trimquote($s) {
        return trim($s,'"');
    }

    public function memoEntry($key,$text) {
        $tkey=explode("\n",$key);
        $ttext=explode("\n","$text");
        $key=trim(implode("\n",array_map('po2js::trimquote',$tkey)));
        $text=trim(implode("\n",array_map('po2js::trimquote',$ttext)));
        //    print sprintf("msgid=[[[%s]]] msgstr=[[[%s]]]\n", $key, $text);
        if ($key && $text)  $this->entry[$key]=$text;
        else if ($key=="") {
            if (stristr($text,"charset=ISO-8859") !== false) {
                $this->encoding='iso';
            }
        }
    }


    public function po2array() {
        if (file_exists($this->pofile)) {
            $pocontent=file_get_contents($this->pofile);
            if ($pocontent) {
                $pocontent.="\n\n";
                preg_match_all('/^msgid (?P<msgid>".*?)msgstr (?P<msgstr>".*?")\n\n/ms', $pocontent, $matches, PREG_SET_ORDER);
                foreach($matches as $m) {
                    //  $msgid = str_replace("\n", " ", $m['msgid']);
                    //$msgstr = str_replace("\n", " ", $m['msgstr']);
                    $this->memoEntry($m['msgid'], $m['msgstr']);
                }
            }
        }

    }

    public function po2json() {
        $this->po2array();
        if (count($this->entry) > 0) {
        $js=json_encode($this->entry);
        if ($this->encoding=="iso") $js=utf8_encode($js);
        return $js;
        } else return "";
       
    }
}

// mettre 2 retour chariots en fin de ligne

$c=new po2js($argv[1]);
$c->po2array();
print $c->po2json();
?>