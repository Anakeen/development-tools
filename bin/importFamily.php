<?php

require_once "initializeAutoloader.php";

use Dcp\DevTools\ImportFamily\ImportFamily;
use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;

$getopt = new Getopt([
    (new Option('u', 'url', Getopt::REQUIRED_ARGUMENT))
        ->setDescription('Dynacase Control url'),
    (new Option('p', 'port', Getopt::REQUIRED_ARGUMENT))
        ->setDescription('Dynacase Control port')
        ->setDefaultValue(80),
    (new Option('f', 'familyPath', Getopt::REQUIRED_ARGUMENT))
        ->setDescription('path of the directory where to import the family')
        ->setValidation(
            function ($path) {
                if (!is_dir($path)) {
                    print "$path is not a directory";
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
    "Usage: %s [options] -- [additional cli options]\\n\\n
additional cli options are passed directly to the remote wiff command.\\n\\n"
);

try {
    $getopt->parse();

    if (isset($getopt['help'])) {
        echo $getopt->getHelpText();
        exit();
    }

    $unexpectedValueErrors = [];

    if (!isset($getopt['familyPath'])) {
        $unexpectedValueErrors['familyPath'] = "You need to set the path of the directory where 
to import the family with -f or --familyPath";
    }

    if (0 < count($unexpectedValueErrors)) {
        throw new UnexpectedValueException("\n -  "
            . implode("\n -  ", $unexpectedValueErrors) . "\n");
    }

    $options = $getopt->getOptions();
    $options['additional_args'] = $getopt->getOperands();

    $importer = new ImportFamily($options);
    $actionLogs = $importer->importFamily();

    if (count($actionLogs['importedCvsFileNames']) > 0) {
        echo "\nIMPORTED FILES IN : " . $actionLogs['familyPath'] . "\n";

        foreach ($actionLogs['importedCvsFileNames'] as $fileName) {
            echo $fileName . "\n";
        }
    }

    if (count($actionLogs['importedPngFileNames']) > 0) {
        echo "\nIMPORTED FILES IN : " . $actionLogs['infoXmlPath'] . "/Images" . "\n";

        foreach ($actionLogs['importedPngFileNames'] as $fileName) {
            echo $fileName . "\n";
        }
    }

    if (count($actionLogs['installProcessAdded']) > 0) {
        echo "\nADDED POST-INSTALL PROCESS IN "
            . $actionLogs['infoXmlPathName'] . ":\n";

        foreach ($actionLogs['installProcessAdded'] as $process) {
            echo $process . "\n";
        }
    }

    if (count($actionLogs['upgradeProcessAdded']) > 0) {
        echo "\nADDED POST-UPGRADE PROCESS IN "
            . $actionLogs['infoXmlPathName'] . ":\n";
        foreach ($actionLogs['upgradeProcessAdded'] as $process) {
            echo $process . "\n";
        }
    }
} catch (UnexpectedValueException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $getopt->getHelpText();
    exit(1);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
