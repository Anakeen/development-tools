<?php


function linkToSources($local, $context)
{

    $contextFiles = explode("\n", `find $context -type f`);
    $localFiles = explode("\n", `find $local -type f`);
    $possibleFiles = array();
    foreach ($localFiles as $lFile) {
        $baselFile = basename($lFile);
        if (!isset($possibleFiles[$baselFile])) {
            $possibleFiles[$baselFile] = $lFile;
        } else {
            $possibleFiles[$baselFile] = false; // no double accepted
        }
    }

    foreach ($contextFiles as $cFile) {
        if (strstr($cFile,'/.')) continue;
        $basecFile = basename($cFile);
        if ($basecFile[0] == '.') continue;
        $orifile = $possibleFiles[$basecFile];
        if ($orifile) {
            // "mv $cFile $cFile.lnkori\n";
            rename($cFile, "$cFile.lnkori");
            // "ln -s  $orifile $cFile\n";
            symlink($orifile, $cFile);
        }
    }
}

function deleteLinkToSources($context) {
     $contextFiles = explode("\n", `find $context -type f -name "*lnkori"`);
    foreach ($contextFiles as $cFile) {
        if ($cFile) {
       // print $cFile." ".substr($cFile,0,-7)."\n";

        rename($cFile, substr($cFile,0,-7));
        }
    }
}

function linkUsage($text) {
    global $argv;
    print "$text\n";
    print "Usage ".$argv[0]." : links to source directory\n";
    print "\tcontext=<directory context>\n";
    print "\tlocal=<source context>\n";
    print "\treset (to delete links)\n";
    exit(1);
}

$options = getopt(null, array(

        'local:',
        'context:',
        'reset::',
    ));


$local = $options["local"];
$context = $options["context"];
if (!$local || !$context) linkUsage("need local and context directory");
if (!is_dir($local)) linkUsage("cannot access local directory");
if (!is_dir($context)) linkUsage("cannot access context directory");
if (array_key_exists("reset",$options) ){
    print "restore original files...";
    deleteLinkToSources($context);
} else {
    print "link to source...";
    linkToSources($local, $context);
}
print "done\n";
//
