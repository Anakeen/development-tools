<?php

require_once "initializeAutoloader.php";

use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;

use Dcp\DevTools\Template\WorkflowStructure;
use Dcp\DevTools\Template\WorkflowClass;
use Dcp\DevTools\Template\WorkflowInfo;
use Dcp\DevTools\Utils\ConfigFile;

$getopt = new Getopt(array(
    (new Option('s', 'sourcePath', Getopt::REQUIRED_ARGUMENT))->setDescription('source Path (needed)')->setValidation(function ($path) {
        if (!is_dir($path)) {
            print "$path is not a valid directory";
            return false;
        }
        return true;
    }),
    (new Option('n', 'name', Getopt::REQUIRED_ARGUMENT))->setDescription('name of family (needed)'),
    (new Option('m', 'namespace', Getopt::REQUIRED_ARGUMENT))->setDescription('namespace of workflow (needed)'),
    (new Option('a', 'application', Getopt::REQUIRED_ARGUMENT))->setDescription('associated application (needed)'),
    (new Option('p', 'parent', Getopt::REQUIRED_ARGUMENT))->setDescription('name of the parent'),
    (new Option('t', 'title', Getopt::REQUIRED_ARGUMENT))->setDescription('title of the workflow'),
    (new Option('f', 'force', Getopt::NO_ARGUMENT))->setDescription('force the write if the file exist'),
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

    if (!isset($getopt['application'])) {
        $error[] = "You need to set the name of the application of the family -a or --application";
    }

    if (!isset($getopt['sourcePath'])) {
        $error[] = "You need to set the sourcepath of the application -s or --sourcePath";
    }

    $outputPath = $getopt['sourcePath'] . DIRECTORY_SEPARATOR . $getopt["application"];

    if (!is_dir($outputPath)) {
        $error[] = "The path of the application doesn't exist. Have you initialized it ?";
    }

    if (!empty($error)) {
        echo join("\n", $error);
        echo "\n" . $getopt->getHelpText();
        exit(42);
    }

    $inputDir = $getopt["sourcePath"];

    $force = $getopt->getOption("force") ? true : false;

    $renderOptions = $getopt->getOptions();

    if (isset($renderOptions["title"]) && !mb_detect_encoding($renderOptions["title"], 'UTF-8', true)) {
        $encoding = mb_detect_encoding($renderOptions["title"], "CP1252,CP1251,UTF-8");
        if (!$encoding) {
            throw new Exception("Unable to detect the encoding of the title args, try to change the encoding of your shell");
        }
        $renderOptions["title"] = mb_convert_encoding($renderOptions["title"], "UTF-8", $encoding);
    }

    $config = new ConfigFile($inputDir);

    $csvParam = $config->get(
        'csvParam', [
        "enclosure" => '"',
        "delimiter" => ';'
    ], ConfigFile::GET_MERGE_DEFAULTS
    );

    $renderOptions["enclosure"] = $csvParam["enclosure"];
    $renderOptions["delimiter"] = $csvParam["delimiter"];
    $renderOptions["output"] = $outputPath;

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