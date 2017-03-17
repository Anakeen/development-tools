<?php

require_once "initializeAutoloader.php";

use Dcp\DevTools\PoToCsv\PoToCsv;
use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;

$getopt = new Getopt([
    (new Option('s', 'source', Getopt::REQUIRED_ARGUMENT))
        ->setDescription('directory path where to start the search for .po files')
        ->setValidation(
            function ($path) {
                if (!is_dir($path)) {
                    print "$path is not a directory\n";
                    return false;
                }
                return true;
            }
        ),
    (new Option('o', 'output', Getopt::REQUIRED_ARGUMENT))
        ->setDescription('file path of the .csv to create')
        ->setValidation(
            function ($path) {
                if (is_file($path)) {
                    print "Cannot create the output file, a file named $path already exist\n";
                    return false;
                }
                return true;
            }
        ),
    (new Option('q', 'quiet', Getopt::NO_ARGUMENT)),
    (new Option('h', 'help', Getopt::NO_ARGUMENT))
        ->setDescription(
            'show the usage message'
        )
]);
$getopt->setBanner(
    "Usage: %s [options] -- [additional cli options]\n\n
additional cli options are passed directly to the remote wiff command.\n\n"
);

$getopt->parse();

if (isset($getopt['help'])) {
    echo $getopt->getHelpText();
    exit();
}

$options = $getopt->getOptions();

$converter = new PoToCsv($options);
$actionLogs = $converter->convert();

if (count($actionLogs['convertedPoFiles']) > 0) {
    echo "\nCONVERTED FILES :\n";

    foreach ($actionLogs['convertedPoFiles'] as $fileName) {
        echo $fileName . "\n";
    }
}

echo "\nCREATED FILE : " . $getopt['output'] . "\n";
