<?php

require_once "initializeAutoloader.php";

use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;

use Dcp\DevTools\Template\Action;

$getopt = new Getopt(array(
    (new Option('f', 'force', Getopt::NO_ARGUMENT))->setDescription('force the write if the file exist'),
    (new Option('o', 'output', Getopt::REQUIRED_ARGUMENT))->setDescription('output Path')->setValidation(function ($path) {
        return is_dir($path);
    }),
    (new Option('n', 'name', Getopt::REQUIRED_ARGUMENT))->setDescription('name of the action (needed)'),
    (new Option('i', 'i18n', Getopt::REQUIRED_ARGUMENT))->setDescription('i18n prefix'),
    (new Option('l', 'layout', Getopt::NO_ARGUMENT))->setDescription('with a layout file (default : no layout)'),
    (new Option('s', 'script', Getopt::NO_ARGUMENT))->setDescription('name of the script file (default : no script)'),
    (new Option('r', 'root', Getopt::NO_ARGUMENT))->setDescription('is root (default : no)'),
    (new Option('a', 'acl', Getopt::REQUIRED_ARGUMENT))->setDescription('name of the acl (default : no ACL)'),
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

    if (!isset($getopt['output']) && (isset($getopt["layout"]) || isset($getopt["function"]))) {
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

    if (!isset($renderOptions["i18n"])) {
        $renderOptions["i18n"] = false;
    }

    $actionTemplate = new Action();
    print $actionTemplate->render($renderOptions, $outputPath, $force);
} catch (UnexpectedValueException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $getopt->getHelpText();
    exit(1);
}
