Dynacase Development tools
==========================

_About_  

This repository provide config file for use 'php\_beautifier' and 'php\_codesniffer' for dynacase development

Getting Help
------------

### PHP code sniffer

[PHP_CodeSniffer tokenises PHP, JavaScript and CSS files and detects violations of a defined set of coding standards](http://pear.php.net/package/PHP_CodeSniffer) .  

### PHP beautifier

[Beautifier for PHP](http://pear.php.net/package/PHP_Beautifier) . 

### JS minifier

[Minfier for JS](https://github.com/rgrove/jsmin-php)

Control the PHP code
------------

Install phpcs program from pear project (version 1.3.0 needed).

    pear install PHP_CodeSniffer

To control a PHP file use this command

    phpcs --standard=<install-devtools>/development-tools/3.1/phpCSDynacase/ Class/Fdl/Class.DocWait.php  
     
    FILE: Class/Fdl/Class.DocWait.php
    --------------------------------------------------------------------------------
    FOUND 1 ERROR(S) AFFECTING 1 LINE(S)
    --------------------------------------------------------------------------------
     8 | ERROR | There must be exactly one blank line before the class comment
    --------------------------------------------------------------------------------
    
    Time: 0 seconds, Memory: 5.25Mb


The option --standard indicate the configuration which must be used to control code. The directory '3.1/phpCSDynacase' include configuration used for dynacase code.

Verify with eclipse IDE
------------

You could verify code using eclipse with the plugins phpTools [PHPsrc](http://www.phpsrc.org/).

PHP Tool CodeSniffer	1.2.6.R20100912000000	org.phpsrc.eclipse.pti.tools.codesniffer.feature.group

To configure php tools code sniffer, go to preferences window.
Go to phpCodeSniffer part and add a "Custom CodeSniffer Standard". Indicate the path to "phpCSDynacase" directory and save it.
You can use "Validate" menu from the PHP file context menu to see problems.


Beautify a PHP Code
------------

If you have a PHP code not already conform to standard you may use the php_beautifier

Install php_beautifier program from pear project (version 0.1.15 needed).

    pear install PHP_Beautifier

Set a symbolic link to Dynacase.filter.php file from install directory of PHP_Beautifier.

    root@luke:/usr/share/php/PHP/Beautifier/Filter# 
       ln -s <install_devtools>/development-tools/3.1/phpBeautifier/Dynacase.filter.php .

Use phpbo programs to beautify the code.

    $ <install_devtools>/developement-tools/3.1/phpBeautifier/phpbo Action/Fdl/editicon.php 

The rules used are based on PEAR standard and conserve blank lines.

Minify a JS Code
------------

If you have a JS code and you want to minify it, you may use jsmin.php

Usage:

    php jsmin.php file_to_minfy.js file_to_output.js
    
    or

    php jsmin.php <file_to_minify.js >file_to_output.js

Jsmin will take the JS code from file_to_minify.js and create a file_to_output.js to put the minfiy code in.

If file_to_output.js already exists, it will be overwritten.

If no file_to_output.js is given, the minify code will be outuput on stdout

if no file_to_minify is given, stdin will be read and minify