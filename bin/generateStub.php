<?php

require_once 'initializeAutoloader.php';

use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;
use Dcp\DevTools\Stub\Stub;

$getopt = new Getopt(array(
    (new Option('o', 'output', Getopt::REQUIRED_ARGUMENT))->setDescription('output dir (nedded)'),
    (new Option('s', 'sourcePath', Getopt::REQUIRED_ARGUMENT))->setDescription('path of the source of the module (nedded)')
        ->setValidation(function ($inputDir) {
            if (!is_dir($inputDir)) {
                print "The input dir must be a valid dir ($inputDir)";
                return false;
            }
            return true;
        }),
    (new Option('f', 'file', Getopt::REQUIRED_ARGUMENT)),
    (new Option('h', 'help', Getopt::NO_ARGUMENT))->setDescription('show the usage message')
));

try {
    $getopt->parse();

    if (isset($getopt["help"])) {
        echo $getopt->getHelpText();
        exit(0);
    }

    $error = array();
    if (!isset($getopt['output'])) {
        $error[] = "You need to set the output path -o or --output";
    }

    if (!isset($getopt['s'])) {
        $error[] = "You need to set the output path for the file -s or --sourcePath";
    }

    if (!empty($error)) {
        echo join("\n", $error);
        echo "\n" . $getopt->getHelpText();
        exit(42);
    }

    if (!is_dir($getopt['output'])) {
        mkdir($getopt['output'], 0777,true);
    }

    $inputDir = $getopt['sourcePath'];

    $realDir = realpath($inputDir);
    if (is_dir($realDir)) {
        $inputDir = $realDir;
    }

    $globRecursive = function ($pattern, $flags = 0) use (&$globRecursive) {

        $files = glob($pattern, $flags);

        foreach (glob(dirname($pattern) . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, $globRecursive($dir . DIRECTORY_SEPARATOR . basename($pattern), $flags));
        }

        return $files;
    };

    if (!is_file($inputDir . DIRECTORY_SEPARATOR . 'build.json')) {
        throw new Exception("The build.json doesn't exist ($inputDir)");
    }
    $conf = json_decode(file_get_contents($inputDir . DIRECTORY_SEPARATOR . 'build.json'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("The build.json is not a valid JSON file ($inputDir)");
    }
    if (!isset($conf["moduleName"])) {
        throw new Exception("The build.json doesn't not contain the module name ($inputDir)");
    }
    if (!isset($conf["csvParam"])) {
        $conf["csvParam"] = array();
    }
    if (!isset($conf["csvParam"]["enclosure"])) {
        $conf["csvParam"]["enclosure"] = '"';
    }
    if (!isset($conf["csvParam"]["delimiter"])) {
        $conf["csvParam"]["delimiter"] = ';';
    }

    $enclosure = $conf["csvParam"]["enclosure"];
    $delimiter = $conf["csvParam"]["delimiter"];

    if (isset($getopt['file']))
    {
        $files = [$getopt['file']];
    }
    else
    {
        $files = array_merge($globRecursive($inputDir . DIRECTORY_SEPARATOR . "*__STRUCT.csv"), $globRecursive($inputDir . DIRECTORY_SEPARATOR . "*__WFL.csv"));
    }
    foreach ($files as $currentFile) {
        $stub = new Stub($enclosure, $delimiter);
        $stub->generate($currentFile, $getopt['output']);
    }

} catch (UnexpectedValueException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $getopt->getHelpText();
    exit(1);
}
