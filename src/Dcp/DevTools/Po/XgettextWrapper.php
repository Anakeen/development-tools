<?php

namespace Dcp\DevTools\Po;

use Dcp\DevTools\Template\Template;

class XgettextWrapper
{

    protected $getTextPath = null;

    public function __construct($getTextPath = "")
    {
        $this->getTextPath = $getTextPath;
    }

    public function xgettext($options)
    {
        $cmd = $this->getTextPath . "xgettext " . $options;
        exec($cmd, $out, $var);
        if ($var != 0) {
            throw new Exception("Exec : $cmd - " . print_r($out, true));
        }
    }

    public function msgmerge($options)
    {
        $cmd = $this->getTextPath . "msgmerge " . $options;
        exec($cmd, $out, $var);
        if ($var != 0) {
            throw new Exception("Exec : $cmd - " . print_r($out, true));
        }
    }

    public function msginit($options)
    {
        //$mustache = new \Mustache_Engine();
//        $isWin = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
//        if (!$isWin) {
//            $cmd = $this->getTextPath . "msginit " . $mustache->render(" --locale={{{lang}}} --no-translator --input={{{potFile}}} --output={{{poTarget}}}", $options);
//            exec($cmd, $out, $var);
//        }
//        if ($isWin || $var != 0) {
            $template = new Template();
            $template->main_render("msginit", $options, $options["poTarget"]);
            $this->msgmerge(sprintf(" --sort-output --no-fuzzy-matching -o %s %s %s", $options["poTarget"] . '.new', $options["poTarget"], $options["potFile"]));
            rename($options["poTarget"] . '.new', $options["poTarget"]);
        //}
    }

    public function msgcat($options)
    {
        $cmd = $this->getTextPath . "msgcat " . $options;
        exec($cmd, $out, $var);
        if ($var != 0) {
            throw new Exception("Exec : $cmd - " . print_r($out, true));
        }
    }

} 