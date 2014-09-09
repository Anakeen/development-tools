<?php

require_once "initializeAutoloader.php";

use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;

use dcp\DevTools\Template\FamilyClass;
use dcp\DevTools\Template\FamilyStructure;
use dcp\DevTools\Template\FamilyParam;
use dcp\DevTools\Template\FamilyInfo;
use dcp\DevTools\Template\WorkflowStructure;
use dcp\DevTools\Template\WorkflowClass;
use dcp\DevTools\Template\WorkflowInfo;

$getopt = new Getopt(array(
    (new Option('f', 'force', Getopt::NO_ARGUMENT))->setDescription('force the write if the file exist'),
    (new Option('o', 'output', Getopt::REQUIRED_ARGUMENT))->setDescription('output Path (needed)')->setValidation(function ($path) {
        if (!is_dir($path)) {
            print "$path is not a valid directory";
            return false;
        }
        return true;
    }),
    (new Option('n', 'name', Getopt::REQUIRED_ARGUMENT))->setDescription('name of family (needed)'),
    (new Option('m', 'namespace', Getopt::REQUIRED_ARGUMENT))->setDescription('namespace of family (needed)'),
    (new Option('p', 'parent', Getopt::REQUIRED_ARGUMENT))->setDescription('name of the parent'),
    (new Option('t', 'title', Getopt::REQUIRED_ARGUMENT))->setDescription('title of the family'),
    (new Option('i', 'icon', Getopt::REQUIRED_ARGUMENT))->setDescription('icon of the family'),
    (new Option('w', 'workflow', Getopt::NO_ARGUMENT))->setDescription('create a workflow (same name than the current family)'),
    (new Option('h', 'help', Getopt::NO_ARGUMENT))->setDescription('show the usage message'),
));

try {
    $getopt->parse();

    if (isset($getopt["help"])) {
        echo $getopt->getHelpText();
        exit(0);
    }

    $error = array();
    if (!isset($getopt['name'])) {
        $error[] = "You need to set the name of the application -n or --name";
    }

    if (!isset($getopt['namespace'])) {
        $error[] = "You need to set the name of the application -m or --namespace";
    }

    if (!isset($getopt['output'])) {
        $error[] = "You need to set the output path for the file -o or --output";
    }

    if (!empty($error)) {
        echo join("\n", $error);
        echo "\n" . $getopt->getHelpText();
        exit(42);
    }

    $outputPath = $getopt->getOption("output");
    $force = $getopt->getOption("force") ? true : false;

    $renderOptions = $getopt->getOptions();

    $template = new FamilyStructure();
    $template->render($renderOptions, $outputPath, $force);
    $template = new FamilyParam();
    $template->render($renderOptions, $outputPath, $force);
    $template = new FamilyClass();
    $template->render($renderOptions, $outputPath, $force);
    $template = new FamilyInfo();
    print $template->render($renderOptions);

    if (isset($getopt["workflow"])) {
        if (isset($renderOptions["parent"])) {
            unset($renderOptions["parent"]);
        }
        $template = new WorkflowStructure();
        $template->render($renderOptions, $outputPath, $force);
        $template = new WorkflowClass();
        $template->render($renderOptions, $outputPath, $force);
        $template = new WorkflowInfo();
        print $template->render($renderOptions);
    }

} catch (UnexpectedValueException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $getopt->getHelpText();
    exit(1);
}