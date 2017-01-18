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
        $cmd = escapeshellarg($this->getTextPath . "xgettext") . " " . $options;
        exec($cmd, $out, $var);
        if ($var != 0) {
            throw new Exception("Exec : $cmd - " . print_r($out, true));
        }
    }

    public function msgmerge($options)
    {
        $cmd = escapeshellarg($this->getTextPath . "msgmerge") . " " . $options;
        exec($cmd, $out, $var);
        if ($var != 0) {
            throw new Exception("Exec : $cmd - " . print_r($out, true));
        }
    }

    public function msginit($options)
    {
        $template = new Template();
        $template->mainRender("msginit", $options, $options["poTarget"]);
        $this->msgmerge(sprintf(" --sort-output --no-fuzzy-matching -o %s %s %s", escapeshellarg($options["poTarget"] . '.new'), escapeshellarg($options["poTarget"]), escapeshellarg($options["potFile"])));
        rename($options["poTarget"] . '.new', $options["poTarget"]);
    }

    public function msgcat($options)
    {
        $cmd = escapeshellarg($this->getTextPath . "msgcat") . " " . $options;
        exec($cmd, $out, $var);
        if ($var != 0) {
            throw new Exception("Exec : $cmd - " . print_r($out, true));
        }
    }
}
