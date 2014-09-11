<?php

namespace Dcp\DevTools\Template;

class FamilyInfo extends Template{
    public function render($arguments)
    {
        if (!isset($arguments["name"]) || !$this->checkLogicalName($arguments["name"])) {
            throw new Exception("You need to set the name of the family with a valid name " . $this->logicalNameRegExp);
        }
        return parent::render("family_info", $arguments);
    }
} 