<?php


function linkToSources($local, $context)
{
    $appNames = array("ACCESS", "API", "APPMNG", "AUTHENT",
            "CORE", "DATA", "DAV", "DCPTEST", "Docs",
            "EXTERNALS", "FDC", "FDL", "FGSEARCH", "FREEDOM", "FUSERS", "GENERIC", "ONEFAM ", "STYLE ",
            "TOOLBOX", "USERCARD", "USERS", "VAULT ", "WHAT");
    $apps = "$context/".implode(" $context/", $appNames);

    $contextFiles = explode("\n", `find $apps -type f`);
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
    $c = 0;
    foreach ($contextFiles as $cFile) {
        if (strstr($cFile, '/.')) continue;
        $basecFile = basename($cFile);
        if ($basecFile[0] == '.') continue;
        $orifile = $possibleFiles[$basecFile];
        if ($orifile) {
            // "mv $cFile $cFile.lnkori\n";
            rename($cFile, "$cFile.lnkori");
            // "ln -s  $orifile $cFile\n";
            symlink($orifile, $cFile);
            $c++;
        }
    }
    return $c;
}

function deleteLinkToSources($context)
{
    $contextFiles = explode("\n", `find $context -type f -name "*lnkori"`);
    $c = 0;
    foreach ($contextFiles as $cFile) {
        if ($cFile) {
            // print $cFile."".substr($cFile,0,-7)."\n";

            rename($cFile, substr($cFile, 0, -7));
            $c++;
        }
    }
    return $c;
}

function linkUsage($text)
{
    global $argv;
    print "$text\n";
    print "Usage ". $argv[0] . ": links to source directory\n";
    print "\tcontext=<directory context>\n";
    print "\tsource=<source context>\n";
    print "\treset (to delete links)\n";
    exit(1);
}

$options = getopt(null, array(

    'source:',
    'context:',
    'reset::',
));


$local = $options["source"];
$context = $options["context"];
if (!$context) linkUsage("need context directory");
if (!is_dir($context)) linkUsage("cannot access context directory");
if (array_key_exists("reset", $options)) {
    print "restore original files...";
    $c = deleteLinkToSources($context);
} else {

    if (!$local) linkUsage("need source directory");
    if (!is_dir($local)) linkUsage("cannot access source directory");
    print "link to source...";
    $c = linkToSources($local, $context);
}
print "$c files processed done\n";
//
