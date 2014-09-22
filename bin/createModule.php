<?php

require_once "initializeAutoloader.php";

use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;

use Dcp\DevTools\Template\InfoXml;
use Dcp\DevTools\Template\Module;
use Dcp\DevTools\Template\Application;
use Dcp\DevTools\Template\ApplicationParameter;
use Dcp\DevTools\ExtractPo\ApplicationPo;
use Dcp\DevTools\ExtractPo\JavascriptPo;
use Dcp\DevTools\ExtractPo\FamilyPo;
use Dcp\DevTools\ExtractPo\ModulePo;
use Dcp\DevTools\Template\BuildConf;

$getopt = new Getopt(array(
    (new Option('o', 'output', Getopt::REQUIRED_ARGUMENT))->setDescription('output Path (needed)')->setValidation(function ($path) {
        if (!is_dir($path)) {
            print "The output dir must be a valid dir ($path)";
            return false;
        }
        return true;
    }),
    (new Option('n', 'name', Getopt::REQUIRED_ARGUMENT))->setDescription('name of the module (needed)'),
    (new Option('a', 'application', Getopt::REQUIRED_ARGUMENT))->setDescription('associated application (list of app name separeted by ,)'),
    (new Option('l', 'lang', Getopt::REQUIRED_ARGUMENT))->setDescription('list of locale (list of locale separeted by ,) default (fr,en)'),
    (new Option('x', 'external', Getopt::NO_ARGUMENT))->setDescription('with external file'),
    (new Option('s', 'style', Getopt::NO_ARGUMENT))->setDescription('with style directory'),
    (new Option('p', 'api', Getopt::NO_ARGUMENT))->setDescription('with api directory'),
    (new Option('e', 'enclosure', Getopt::OPTIONAL_ARGUMENT))->setDescription('enclosure of the CSV generated (default : " )'),
    (new Option('d', 'delimiter', Getopt::REQUIRED_ARGUMENT))->setDescription('delimiter of the CSV generated (default : ; )'),
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

    if (!isset($renderOptions["lang"])) {
        $renderOptions["lang"] = "fr,en";
    }

    if (!isset($renderOptions["enclosure"])) {
        $renderOptions["enclosure"] = '"';
    }

    if (!isset($renderOptions["delimiter"])) {
        $renderOptions["delimiter"] = ';';
    }

    if($renderOptions["enclosure"] === 1) {
        $renderOptions["enclosure"] = "";
    }

    $renderOptions["lang"] = explode(",", $renderOptions["lang"]);

    $template = new Module();
    $template->render($renderOptions, $outputPath, $force);

    $outputPath = $outputPath.DIRECTORY_SEPARATOR.$renderOptions["name"];

    if (isset($renderOptions["application"])) {
        $applications = explode(",", $renderOptions["application"]);
        foreach($applications as $currentApplication) {
            $applicationPath = $outputPath . DIRECTORY_SEPARATOR . $currentApplication;
            $applicationRenderOptions = array(
                "name" => $currentApplication,
                "i18n" => $currentApplication
            );
            $applicationTemplate = new Application();
            $applicationTemplate->render($applicationRenderOptions, $applicationPath, $force);
            $applicationParamTemplate = new ApplicationParameter();
            $applicationParamTemplate->render($applicationRenderOptions, $applicationPath, $force);
        }
    }

    $template = new BuildConf();
    $template->render($renderOptions, $outputPath, $force);

    $template = new InfoXml();
    $template->render($renderOptions, $outputPath, $force);

    $extractor = new ModulePo($outputPath);
    $extractor->extractPo();

    $extractor = new ApplicationPo($outputPath);
    $extractor->extractPo();

    $extractor = new JavascriptPo($outputPath);
    $extractor->extractPo();

    $extractor = new FamilyPo($outputPath);
    $extractor->extractPo();

} catch (UnexpectedValueException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $getopt->getHelpText();
    exit(1);
}