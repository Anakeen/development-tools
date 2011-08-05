<?php
require_once('PHP/Beautifier/Filter/Pear.filter.php');
class PHP_Beautifier_Filter_Dynacase extends PHP_Beautifier_Filter_Pear
{
  function t_comment($sTag) 
  { 
    return PHP_Beautifier_Filter::BYPASS;
  }
  function t_whitespace($sTag)
  {
    $match = array();
    // how many new lines can we match?
    preg_match_all("/(\r\n|\r|\n)/s", $sTag, $match);
    if (!empty($match[1])) {
      $newLines = sizeof($match[1]);
      if ($newLines == 2) {
	$this->oBeaut->addNewLineIndent();
      }
      else if ($newLines > 2) {
	$this->oBeaut->addNewLineIndent();
	//$this->oBeaut->addNewLineIndent();
      }
    }
  }
}