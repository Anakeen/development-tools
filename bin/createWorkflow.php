<?php

require_once "initializeAutoloader.php";

use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;

use Dcp\DevTools\Template\WorkflowStructure;
use Dcp\DevTools\Template\WorkflowClass;
use Dcp\DevTools\Template\WorkflowInfo;

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
    (new Option('m', 'namespace', Getopt::REQUIRED_ARGUMENT))->setDescription('namespace of workflow (needed)'),
    (new Option('p', 'parent', Getopt::REQUIRED_ARGUMENT))->setDescription('name of the parent'),
    (new Option('t', 'title', Getopt::REQUIRED_ARGUMENT))->setDescription('title of the workflow'),
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

    $template = new WorkflowStructure();
    $template->render($renderOptions, $outputPath, $force);
    $template = new WorkflowClass();
    $template->render($renderOptions, $outputPath, $force);
    $template = new WorkflowInfo();
    print $template->render($renderOptions);

} catch (UnexpectedValueException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $getopt->getHelpText();
    exit(1);
}