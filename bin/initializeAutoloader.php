<?php

$baseDir = realpath(dirname(__FILE__) . '/..');

require $baseDir
    . DIRECTORY_SEPARATOR . 'src'
    . DIRECTORY_SEPARATOR . 'dcp'
    . DIRECTORY_SEPARATOR . 'devTools'
    . DIRECTORY_SEPARATOR . 'Autoloader.php';

dcp\DevTools\Autoloader::register();

