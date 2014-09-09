<?php

require_once "initializeAutoloader.php";

use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;

$getopt = new Getopt(array(
    (new Option('v', 'version', Getopt::REQUIRED_ARGUMENT))->setValidation(function ($value) {
        return false;
    }),
    (new Option('h', 'help', Getopt::REQUIRED_ARGUMENT))->setDescription('help message')
));

try {
    $getopt->parse();

    if ($getopt['version']) {
        echo "Getopt example v0.0.1\n";
        exit(0);
    }

    var_export($getopt->getOperands());


} catch (UnexpectedValueException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $getopt->getHelpText();
    exit(1);
}
