<?php

require_once __DIR__ . '/' . 'initializeAutoloader.php';

use Dcp\DevTools\CreateWorkflow\CreateWorkflow;
use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;

$getopt = new Getopt(array(
    (new Option('s', 'sourcePath', Getopt::REQUIRED_ARGUMENT))
        ->setDescription('source Path (needed)')
        ->setValidation(function ($path) {
            if (!is_dir($path)) {
                print "$path is not a valid directory";
                return false;
            }
            return true;
        }),
    (new Option('n', 'name', Getopt::REQUIRED_ARGUMENT))
        ->setDescription('name of family (needed)'),
    (new Option('m', 'namespace', Getopt::REQUIRED_ARGUMENT))
        ->setDescription('namespace of workflow (needed)'),
    (new Option('o', 'outputDir', Getopt::REQUIRED_ARGUMENT))
        ->setDescription('output directory (relative to sourcepath)'),
    (new Option('p', 'parent', Getopt::REQUIRED_ARGUMENT))
        ->setDescription('name of the parent'),
    (new Option('t', 'title', Getopt::REQUIRED_ARGUMENT))
        ->setDescription('title of the workflow'),
    (new Option('i', 'icon', Getopt::REQUIRED_ARGUMENT))
        ->setDescription('icon of the family'),
    (new Option(null, 'no-backup', Getopt::NO_ARGUMENT))
        ->setDescription('Do not backup overwritten files'),
    (new Option('q', 'quiet', Getopt::NO_ARGUMENT)),
    (new Option('h', 'help', Getopt::NO_ARGUMENT))
        ->setDescription('show the usage message'),
));

try {
    $getopt->parse();

    if (isset($getopt['help'])) {
        echo $getopt->getHelpText();
        exit(0);
    }

    $error = array();
    if (!isset($getopt['name'])) {
        $error[] = 'family name is required';
    }

    if (!isset($getopt['namespace'])) {
        $error[] = 'namespace is required';
    }

    if (!isset($getopt['outputDir'])) {
        $error[] = 'outputDir is required';
    }

    if (!isset($getopt['sourcePath'])) {
        $error[] = 'sourcePath is required';
    }

    $outputPath = $getopt['sourcePath'] . DIRECTORY_SEPARATOR . $getopt['outputDir'];

    if (!is_dir($outputPath)) {
        $error[] = $outputPath . ' does not exists.';
    }

    if (!empty($error)) {
        echo implode("\n", $error);
        echo "\n" . $getopt->getHelpText();
        exit(1);
    }

    $inputDir = $getopt['sourcePath'];

    $force = $getopt->getOption('force') ? true : false;

    $renderOptions = $getopt->getOptions();

    if (isset($renderOptions["title"]) && !mb_detect_encoding($renderOptions["title"], 'UTF-8', true)) {
        $encoding = mb_detect_encoding($renderOptions["title"], "CP1252,CP1251,UTF-8");
        if (!$encoding) {
            throw new Exception("Unable to detect the encoding of the title args, try to change the encoding of your shell");
        }
        $renderOptions["title"] = mb_convert_encoding($renderOptions["title"], "UTF-8", $encoding);
    }

    if (isset($renderOptions["icon"]) && !mb_detect_encoding($renderOptions["icon"], 'UTF-8', true)) {
        $encoding = mb_detect_encoding($renderOptions["icon"], "CP1252,CP1251,UTF-8");
        if (!$encoding) {
            throw new Exception("Unable to detect the encoding of the title args, try to change the encoding of your shell");
        }
        $renderOptions["title"] = mb_convert_encoding($renderOptions["title"], "UTF-8", $encoding);
    }

    if (isset($renderOptions["icon"]) && !mb_detect_encoding($renderOptions["icon"], 'UTF-8', true)) {
        $encoding = mb_detect_encoding($renderOptions["icon"], "CP1252,CP1251,UTF-8");
        if (!$encoding) {
            throw new Exception("Unable to detect the encoding of the title args, try to change the encoding of your shell");
        }
        $renderOptions["title"] = mb_convert_encoding($renderOptions["title"], "UTF-8", $encoding);
    }

    $workflowCreator = new CreateWorkflow($renderOptions);
    $actionLogs = $workflowCreator->create();

    if (!isset($getopt['quiet']) || $getopt['quiet'] < 1) {
        if (count($actionLogs['overwrittenFiles']) > 0) {
            echo "\n[Warning] Following files have been overwritten " .
                (empty($getopt['no-backup'])
                    ? "(their backup is in " . $actionLogs['backupDir'] . ")"
                    : "(backup disabled)");
            echo "\n- " . implode("\n- ", $actionLogs['overwrittenFiles']) . "\n";
        }
    }

    if (!isset($getopt['quiet']) || $getopt['quiet'] < 2) {
        if (count($actionLogs['importedCsvFileNames']) > 0) {
            echo "\nImported csv files in " . $actionLogs['outputDir'] . ":";
            echo "\n- " . implode("\n- ", $actionLogs['importedCsvFileNames']) . "\n";
        }

        if (count($actionLogs['importedPhpFileNames']) > 0) {
            echo "\nImported csv files in " . $actionLogs['outputDir'] . ":";
            echo "\n- " . implode("\n- ", $actionLogs['importedPhpFileNames']) . "\n";
        }

        if (count($actionLogs['importedImgFileNames']) > 0) {
            echo "\nImported image files in " . $actionLogs['infoXmlPath'] . "/Images:";
            echo "\n- " . implode("\n- ", $actionLogs['importedImgFileNames']) . "\n";
        }

        if (count($actionLogs['installProcessAdded']) > 0) {
            echo "\nadded post-install processes in " . $actionLogs['infoXmlPathName'] . ":";
            echo "\n- " . implode("\n- ", $actionLogs['installProcessAdded']) . "\n";
        }

        if (count($actionLogs['upgradeProcessAdded']) > 0) {
            echo "\nadded post-install processes in " . $actionLogs['infoXmlPathName'] . ":";
            echo "\n- " . implode("\n- ", $actionLogs['upgradeProcessAdded']) . "\n";
        }
    }
} catch (UnexpectedValueException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $getopt->getHelpText();
    exit(1);
}
