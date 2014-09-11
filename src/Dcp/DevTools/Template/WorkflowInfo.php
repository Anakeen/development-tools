<?php

namespace Dcp\DevTools\Template;

class WorkflowInfo extends Template
{
    public function render($arguments)
    {
        if (!isset($arguments["name"]) || !$this->checkLogicalName($arguments["name"])) {
            throw new Exception("You need to set the name of the workflow with a valid name " . $this->logicalNameRegExp);
        }
        return parent::render("workflow_info", $arguments);
    }
} 