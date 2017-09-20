<?php

require_once "initializeAutoloader.php";

use Dcp\DevTools\Deploy\Deploy;
use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;

const DEPLOY_CONFIG_FILE = 'deploy.json';

$getopt = new Getopt([
    (new Option('u', 'url', Getopt::REQUIRED_ARGUMENT))
        ->setDescription('Dynacase Control url'),
    (new Option('p', 'port', Getopt::REQUIRED_ARGUMENT))
        ->setDescription('Dynacase Control port')
        ->setDefaultValue(80),
    (new Option('c', 'context', Getopt::REQUIRED_ARGUMENT))
        ->setDescription('name of the context on the target'),
    (new Option(null, 'action', Getopt::REQUIRED_ARGUMENT))
        ->setDescription('action to execute (install|upgrade)')
        ->setValidation(
            function ($action) {
                if ('install' !== $action && 'upgrade' !== $action) {
                    print "$action is not a valid action.";
                    print "action must be either 'install' or 'upgrade'";
                    return false;
                }
                return true;
            }
        ),
    (new Option('w', 'webinst', Getopt::REQUIRED_ARGUMENT))
        ->setDescription('webinst to deploy. If no webinst provided, a new one will be generated.'),
    (new Option(
        's',
        'sourcePath',
        Getopt::REQUIRED_ARGUMENT
    ))
        ->setDescription('path of the module')
        ->setValidation(
            function ($path) {
                if (!is_dir($path)) {
                    print "$path is not a directory";
                    return false;
                }
                return true;
            }
        ),
    (new Option('a', 'auto-release', Getopt::NO_ARGUMENT))
        ->setDescription('append current timestamp to release to force upgrade'),
    (new Option('q', 'quiet', Getopt::NO_ARGUMENT)),
    (new Option('h', 'help', Getopt::NO_ARGUMENT))
        ->setDescription(
            'show the usage message'
        )
]);
$getopt->setBanner("Usage: %s [options] -- [additional cli options]\n" .
    "\nadditional cli options are passed directly to the remote wiff command.\n\n");

try {
    $getopt->parse();

    if (isset($getopt['help'])) {
        echo $getopt->getHelpText();
        exit();
    }
    $unexpectedValueErrors = [];

    if (!isset($getopt['sourcePath']) && !isset($getopt['webinst'])) {
        $unexpectedValueErrors['sourcePath']
            = "You need to set the sourcepath of the application with -s or --sourcePath";
    }
    if (isset($getopt['w']) && isset($getopt['a']) && $getopt['a'] > 0) {
        $unexpectedValueErrors['auto-release']
            = "--webinst and --auto-release are not compatible";
    }

    if (0 < count($unexpectedValueErrors)) {
        throw new UnexpectedValueException("\n -  " .
            implode(
                "\n -  ",
                $unexpectedValueErrors
            ) . "\n");
    }

    $options = $getopt->getOptions();
    $options['additional_args'] = $getopt->getOperands();

    $deployer = new Deploy($options);
    $result = $deployer->deploy();

    if (!$result['success']) {
        if (!isset($getopt['q']) || $getopt['q'] < 2) {
            print "\nAn error occured on server.\n";
        }
        if (!isset($getopt['q']) || $getopt['q'] < 2) {
            print "\n--- script error:";
            print "\n    " . $result['error'];
            print "\n--- script messages:";
            foreach ($result['warnings'] as $message) {
                print "\n    " . $message;
            }
            if (isset($result['data'])
            ) {
                print "\n--- raw output:";
                if (is_array($result['data'])) {
                    foreach ($result['data'] as $out) {
                        print "\n    " . $out;
                    }
                } else {
                    print "\n    " . $result['data'];
                }
            } else {
                print "\n--- no output from server";
            }
        }
        print "\n";
        exit(1);
    } else {
        if (!isset($getopt['q']) || $getopt['q'] < 2) {
            print "\nsuccess\n";
        }
        if (!isset($getopt['q']) || $getopt['q'] < 1) {
            print "\n--- script messages:";
            foreach ($result['warnings'] as $message) {
                print "\n    " . $message;
            }
            if (isset($result['data'])
            ) {
                print "\n--- raw output:";
                if (is_array($result['data'])) {
                    foreach ($result['data'] as $out) {
                        print "\n    " . $out;
                    }
                } else {
                    print "\n    " . $result['data'];
                }
            } else {
                print "\n--- no output from server";
            }
        }
        print "\n";
    }
} catch (UnexpectedValueException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $getopt->getHelpText();
    exit(1);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
