<?php

require_once "initializeAutoloader.php";

use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;

$getopt = new Getopt(array(
    (new Option('h', 'help', Getopt::NO_ARGUMENT))->setDescription('help message')
));

try {
    $getopt->parse();

    $currentDirName = dirname(__FILE__);

    if ($getopt['help'] || !isset($getopt->getOperands()[0])) {
        echo "DevTools for Dynacase 3.2\n";
        echo "You can access to the sub command : \n";
        listOfSubCommand($currentDirName);
        exit(0);
    }

    $command = $getopt->getOperands()[0];

    if (!file_exists($currentDirName.DIRECTORY_SEPARATOR.$command.".php")) {
        echo "Error : The sub command ".$command." is unkown\n";
        echo "You can access to the sub command : \n";
        listOfSubCommand($currentDirName);
        exit(42);
    }

    unset($argv[1]);

    require($currentDirName . DIRECTORY_SEPARATOR . $command . ".php");


} catch (UnexpectedValueException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $getopt->getHelpText();
    exit(1);
}

function listOfSubCommand($basePath) {
    $scripts = new \DirectoryIterator($basePath);
    foreach ($scripts as $currentScript) {
        /* @var $currentScript \DirectoryIterator */
        if ($currentScript->isDot() || $currentScript->getFilename() === basename(__FILE__) || $currentScript->getFilename() === "initializeAutoloader.php") {
            continue;
        }
        print "\t" . str_replace(".php", "", $currentScript->getFilename()) . "\n";
    }
}
