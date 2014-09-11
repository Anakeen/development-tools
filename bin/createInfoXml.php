<?php

require_once "initializeAutoloader.php";

use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;

use Dcp\DevTools\Template\InfoXml;

$getopt = new Getopt(array(
    (new Option('f', 'force', Getopt::NO_ARGUMENT))->setDescription('force the write if the file exist (needed)'),
    (new Option('o', 'output', Getopt::REQUIRED_ARGUMENT))->setDescription('output Path')->setValidation(function ($path) {
        return is_dir($path);
    }),
    (new Option('n', 'name', Getopt::REQUIRED_ARGUMENT))->setDescription('name of the module (needed)'),
    (new Option('d', 'description', Getopt::REQUIRED_ARGUMENT))->setDescription('description of the module'),
    (new Option('a', 'application', Getopt::REQUIRED_ARGUMENT))->setDescription('associated application'),
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

    $template = new InfoXml();
    $template->render($renderOptions, $outputPath, $force);

} catch (UnexpectedValueException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $getopt->getHelpText();
    exit(1);
}