<?php

set_include_path(__DIR__ . DIRECTORY_SEPARATOR . '..' . PATH_SEPARATOR . get_include_path());

if (! file_exists(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR .'vendor/autoload.php')) {
    throw new \Exception("vendor/autoload.php does not exists. You may have forgotten to run 'composer install'");
}

require 'vendor/autoload.php';
