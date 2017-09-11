<?php

require_once "initializeAutoloader.php";

use Dcp\DevTools\Utils\ConfigFile;
use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;

use Dcp\DevTools\Template\Application;
use Dcp\DevTools\Template\ApplicationParameter;

$getopt = new Getopt(array(
    (new Option('s', 'sourcePath', Getopt::REQUIRED_ARGUMENT))
        ->setDescription('source Path (required)')
        ->setValidation(
            function ($path) {
                if (!is_dir($path)) {
                    print "$path is not a directory\n";
                    return false;
                }
                if (!file_exists($path . '/' . ConfigFile::DEFAULT_FILE_NAME)) {
                    print sprintf("$path does not contains a %s file\n", ConfigFile::DEFAULT_FILE_NAME);
                    return false;
                }
                return true;
            }
        ),
    (new Option('n', 'name', Getopt::REQUIRED_ARGUMENT))->setDescription('name of the application (required)'),
    (new Option('i', 'i18n', Getopt::REQUIRED_ARGUMENT))->setDescription('i18n prefix'),
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

    if (!isset($getopt['s'])) {
        $error[] = "You need to set the path of the source of the application -s or --sourcePath";
    }

    if (!empty($error)) {
        echo join("\n", $error);
        echo "\n".$getopt->getHelpText();
        exit(42);
    }

    $outputPath = $getopt->getOption("s").DIRECTORY_SEPARATOR. $getopt->getOption("n");
    if (!is_dir($outputPath)) {
        if (!@mkdir($outputPath)
            && !is_dir($outputPath)
        ) {
            throw new Exception('could not create output path ' . $outputPath);
        }
    }
    $force = $getopt->getOption("force") ? true : false;

    $renderOptions = $getopt->getOptions();

    $config = new ConfigFile($renderOptions['sourcePath']);

    if (!isset($renderOptions["i18n"])) {
        $renderOptions["i18n"] = false;
    }

    $applicationTemplate = new Application();
    $applicationTemplate->render($renderOptions, $outputPath, $force);
    $applicationParamTemplate = new ApplicationParameter();
    $applicationParamTemplate->render($renderOptions, $outputPath, $force);

    $applications = $config->get('application');
    if(!in_array($getopt->getOption('name'), $applications)) {
        array_push($applications, $getopt->getOption('name'));
        sort($applications);
        $config->set('application', $applications);
        $config->saveConfig();
    }
} catch (UnexpectedValueException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $getopt->getHelpText();
    exit(1);
}
