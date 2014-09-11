<?php

require_once "initializeAutoloader.php";

use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;

use Dcp\DevTools\ExtractPo\ModulePo;
use Dcp\DevTools\ExtractPo\ApplicationPo;
use Dcp\DevTools\ExtractPo\JavascriptPo;
use Dcp\DevTools\ExtractPo\FamilyPo;

$getopt = new Getopt(array(
    (new Option('i', 'input', Getopt::REQUIRED_ARGUMENT))->setDescription('path of the module (needed)')->setValidation(function ($path) {
        if (!is_dir($path)) {
            print "The path of the module ($path)";
            return false;
        }
        return true;
    }),
    (new Option('h', 'help', Getopt::NO_ARGUMENT))->setDescription('show the usage message'),
));

try {
    $getopt->parse();

    $error = array();

    if (!isset($getopt['input'])) {
        $error[] = "You need to set the input path of the project -i or --input";
    }

    if (!empty($error)) {
        echo join("\n", $error);
        echo "\n" . $getopt->getHelpText();
        exit(42);
    }

    $extractor = new ModulePo($getopt['input']);
    $extractor->extractPo();

    $extractor = new ApplicationPo($getopt['input']);
    $extractor->extractPo();

    $extractor = new JavascriptPo($getopt['input']);
    $extractor->extractPo();

    $extractor = new FamilyPo($getopt['input']);
    $extractor->extractPo();


} catch (UnexpectedValueException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $getopt->getHelpText();
    exit(1);
}