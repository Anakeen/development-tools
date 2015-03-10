<?php

require_once "initializeAutoloader.php";

use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;

$getopt = new Getopt(array(
    (new Option('h', 'help', Getopt::NO_ARGUMENT))->setDescription('help message'),
    (new Option('v', 'version', Getopt::NO_ARGUMENT))->setDescription(
        'version'
    )
));

//Bootstrap loader for devtools
/**
 * Check the extensions
 * Call the next script
 */

try {

    // Test extension
    $requiredExtension = array("mbstring", "zlib", "zip", "Phar", "bz2");
    $requiredError = "";
    foreach($requiredExtension as $currentRequired) {
        if (!extension_loaded($currentRequired)) {
            $requiredError .= "\t * $currentRequired \n";
        }
    }

    if ($requiredError !== "") {
        echo "You need the following extensions to run the devtools : \n";
        echo $requiredError;
        echo "You should activate it from php.ini \n";
        exit(42);
    }

    $getopt->parse();

    $currentDirName = dirname(__FILE__);

    $version = join(
        DIRECTORY_SEPARATOR,
        array(__DIR__, '..', 'version.json')
    );
    $version = json_decode(file_get_contents($version), true);

    if ($getopt["version"]) {
        echo sprintf("Devtool version : %s", $version["tag"]) . "\n";
        exit(0);
    }

    if ($getopt['help'] || !isset($getopt->getOperands()[0])) {
        echo "DevTools for Dynacase 3.2\n";
        echo sprintf("Devtool version : %s", $version["tag"])."\n";
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
