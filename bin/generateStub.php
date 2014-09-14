<?php

require_once 'initializeAutoloader.php';

use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;
use Dcp\BuildTools\Stub\Stub;

$getopt = new Getopt(array(
    (new Option('o', 'output', Getopt::REQUIRED_ARGUMENT))->setDescription('output dir (nedded)')->setValidation(function ($dir) {
        if (!is_dir($dir)) {
            print "The output dir must be a valid dir ($dir)";
            return false;
        }
        return true;
    }),
    (new Option('i', 'input', Getopt::REQUIRED_ARGUMENT))->setDescription('input path of the php files (nedded)')
        ->setValidation(function ($inputDir) {
            if (!is_dir($inputDir)) {
                print "The input dir must be a valid dir ($inputDir)";
                return false;
            }
            return true;
        }),
    (new Option('e', 'enclosure', Getopt::OPTIONAL_ARGUMENT))->setDescription('enclosure of the CSV file (default : ")'),
    (new Option('d', 'delimiter', Getopt::REQUIRED_ARGUMENT))->setDescription('delimiter of the CSV file (default : ;)'),
    (new Option('h', 'help', Getopt::NO_ARGUMENT))->setDescription('show the usage message'),
));

try {
    $getopt->parse();

    if (isset($getopt["help"])) {
        echo $getopt->getHelpText();
        exit(0);
    }

    $error = array();
    if (!isset($getopt['input'])) {
        $error[] = "You need to set the input dir of the application -i or --input";
    }

    if (!isset($getopt['output'])) {
        $error[] = "You need to set the output path for the file -o or --output";
    }

    if (!empty($error)) {
        echo join("\n", $error);
        echo "\n" . $getopt->getHelpText();
        exit(42);
    }

    $inputDir = $getopt['input'];

    $realDir = realpath($inputDir);
    if (is_dir($realDir)) {
        $inputDir = $realDir;
    }

    $globRecursive = function ($pattern, $flags = 0) use (&$globRecursive) {

        $files = glob($pattern, $flags);

        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, $globRecursive($dir . '/' . basename($pattern), $flags));
        }

        return $files;
    };

    $enclosure = !isset($getopt["enclosure"]) ? '"' : $getopt["enclosure"];
    $delimiter = !isset($getopt["delimiter"]) ? ';' : $getopt["delimiter"];
    $enclosure = $enclosure === 1 ? "" : $enclosure;



    $files = $globRecursive("$inputDir/*__STRUCT.csv");
    foreach($files as $currentFile) {
        $stub = new Stub($enclosure, $delimiter);
        $stub->generate($currentFile, $getopt['output']);
    }

    $files = $globRecursive("$inputDir/*__WFL.csv");
    foreach ($files as $currentFile) {
        $stub = new Stub($enclosure, $delimiter);
        $stub->generate($currentFile, $getopt['output']);
    }

} catch (UnexpectedValueException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $getopt->getHelpText();
    exit(1);
}
