<?php

require_once 'initializeAutoloader.php';

use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;
use Dcp\DevTools\Webinst\Webinst;

$getopt = new Getopt(array(
    (new Option('s', 'sourcePath', Getopt::REQUIRED_ARGUMENT))->setDescription('path to the source of the module (nedded)')
        ->setValidation(function ($inputDir) {
            if (!is_dir($inputDir)) {
                print "The input dir must be a valid dir ($inputDir)";
                return false;
            }
            return true;
        }),
    (new Option('o', 'output', Getopt::REQUIRED_ARGUMENT))->setDescription(
        'output Path (needed)'
    )->setValidation(
        function ($path) {
            if (!$path) {
                return true;
            }
            if (!is_dir($path)) {
                print "The output dir must be a valid dir ($path)";
                return false;
            }
            return true;
        }
    ),
    (new Option('a', 'auto-release', Getopt::NO_ARGUMENT))
        ->setDescription('append current timestamp to release to force upgrade'),
    (new Option('h', 'help', Getopt::NO_ARGUMENT))->setDescription('show the usage message'),
));

try {
    $getopt->parse();

    if (isset($getopt["help"])) {
        echo $getopt->getHelpText();
        exit(0);
    }

    $error = array();
    if (!isset($getopt['sourcePath'])) {
        $error[] = "You need to set the path to the source of the module -s or --sourcePath";
    }

    $outputPath = isset($getopt['output']) ? $getopt['output'] : false;

    if ($outputPath) {
        $outputPath = realpath($outputPath);
    }

    if (!empty($error)) {
        echo join("\n", $error);
        echo "\n" . $getopt->getHelpText();
        exit(42);
    }

    $webinst = new Webinst($getopt['sourcePath']);
    if (isset($getopt['auto-release'])) {
        $webinst->setConfProperty(
            'release',
            $webinst->getConf('release') . strftime(".%Y%m%d.%H%M%S")
        );
    }
    $webinst->makeWebinst($outputPath);
} catch (UnexpectedValueException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $getopt->getHelpText();
    exit(1);
}
