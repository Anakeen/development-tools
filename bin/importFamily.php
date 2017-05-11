<?php

require_once __DIR__ . '/' . 'initializeAutoloader.php';

use Dcp\DevTools\ImportFamily\ImportFamily;
use Dcp\DevTools\Utils\ConfigFile;
use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;

$getopt = new Getopt([
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
    (new Option('u', 'url', Getopt::REQUIRED_ARGUMENT))
        ->setDescription('Dynacase context url (required)'),
    (new Option('p', 'port', Getopt::REQUIRED_ARGUMENT))
        ->setDescription('Dynacase context port (required)')->setDefaultValue(80),
    (new Option('n', 'name', Getopt::REQUIRED_ARGUMENT))
        ->setDescription('Family name (required)'),
    (new Option('o', 'outputDir', Getopt::REQUIRED_ARGUMENT))
        ->setDescription('Path for the retrieved family files, relative to source path (required)'),
    (new Option(null, 'no-backup', Getopt::NO_ARGUMENT))
        ->setDescription('Do not backup overwritten files'),
    (new Option('q', 'quiet', Getopt::NO_ARGUMENT)),
    (new Option('h', 'help', Getopt::NO_ARGUMENT))
        ->setDescription('Show the usage message')
]);

try {
    $getopt->parse();

    if (isset($getopt['help'])) {
        echo $getopt->getHelpText();
        exit();
    }

    $unexpectedValueErrors = [];

    if (!isset($getopt['name'])) {
        $unexpectedValueErrors['name'] = 'name is required';
    }

    $options = $getopt->getOptions();

    if (!isset($options['outputDir'])) {
        $unexpectedValueErrors['outputDir'] = 'outputDir is required';
    } elseif (!is_dir($options['sourcePath'] . '/' . $options['outputDir'])) {
        $unexpectedValueErrors['outputDir'] = sprintf('%s is not a directory',
            $options['sourcePath'] . '/' . $options['outputDir']);
    }

    if (0 < count($unexpectedValueErrors)) {
        throw new UnexpectedValueException("\n -  "
            . implode("\n -  ", $unexpectedValueErrors) . "\n");
    }

    $importer = new ImportFamily($options);
    $actionLogs = $importer->importFamily();

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
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
