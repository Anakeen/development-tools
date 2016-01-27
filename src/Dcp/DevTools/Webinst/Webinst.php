<?php

namespace Dcp\DevTools\Webinst;

use Dcp\DevTools\Utils\ConfigFile;

class Webinst {

    protected $inputPath;
    protected $conf;
    protected $templateEngine;

    private $WEBINST_EXEC_MASK = 0100;

    public function __construct($inputPath) {
        if (!is_dir($inputPath)) {
            throw new Exception("The input path doesn't exist ($inputPath)");
        }
        $this->inputPath = $inputPath;

        $this->templateEngine = new \Mustache_Engine();

        $config = new ConfigFile($inputPath);

        $this->conf = $config->getConfig();

        if (!isset($this->conf["moduleName"])) {
            throw new Exception(
                sprintf(
                    "%s doesn't not contain the module name.",
                    $config->getConfigFilePath()
                )
            );
        }
        if (!isset($this->conf["version"])) {
            throw new Exception(
                sprintf(
                    "%s doesn't not contain the version.",
                    $config->getConfigFilePath()
                )
            );
        }
        if (!isset($this->conf["release"])) {
            throw new Exception(
                sprintf(
                    "%s doesn't not contain the release.",
                    $config->getConfigFilePath()
                )
            );
        }
    }

    public function makeWebinst($outputPath) {
        $allowedDirectories = array();
        if (isset($this->conf["application"]) && is_array($this->conf["application"])) {
            $allowedDirectories = array_merge($allowedDirectories, $this->conf["application"]);
        }
        if (isset($this->conf["includedPath"]) && is_array($this->conf["includedPath"])) {
            $allowedDirectories = array_merge($allowedDirectories, $this->conf["includedPath"]);
        }
        $contentTar = $this->inputPath.DIRECTORY_SEPARATOR."temp_tar";
        $pharTar = new \PharData($contentTar.".tar");
        $pharTar->startBuffering();
        foreach($allowedDirectories as $allowedDirectory) {
            $addedFiles = $pharTar->buildFromIterator(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator(
                        $this->inputPath . DIRECTORY_SEPARATOR . $allowedDirectory,
                        \FilesystemIterator::SKIP_DOTS
                    )
                ),
                $this->inputPath
            );
            foreach ($addedFiles as $pharFilePath => $systemFilePath) {
                if (!is_dir($systemFilePath) && is_executable($systemFilePath)) {
                    echo "marking $pharFilePath as executable \n";
                    $pharTar[$pharFilePath]->chmod($pharTar[$pharFilePath]->getPerms() | $this->WEBINST_EXEC_MASK);
                }
            }
        }
        $pharTar->stopBuffering();
        $pharTar->compress(\Phar::GZ);
        unset($pharTar);
        unlink($contentTar.".tar");
        $infoXML = $this->templateEngine->render(
            '{{=@ @=}}' . file_get_contents(
                $this->inputPath . DIRECTORY_SEPARATOR . "info.xml"
            ), $this->conf
        );
        $webinstName = $this->getWebinstName();
        $pharTar = new \PharData($this->inputPath . DIRECTORY_SEPARATOR . $this->conf["moduleName"].".tar");
        $pharTar->startBuffering();
        $pharTar->addFromString("info.xml", $infoXML);
        if (file_exists($this->inputPath.DIRECTORY_SEPARATOR."LICENSE")) {
            $pharTar->addFile($this->inputPath.DIRECTORY_SEPARATOR."LICENSE", "LICENSE");
        }
        $pharTar->addFile($contentTar.".tar.gz", "content.tar.gz");
        $pharTar->stopBuffering();
        $pharTar->compress(\Phar::GZ);
        if (!$outputPath) {
            $outputPath = $this->inputPath;
        }
        rename($this->inputPath . DIRECTORY_SEPARATOR . $this->conf["moduleName"].".tar.gz",
            $outputPath . DIRECTORY_SEPARATOR . $webinstName . ".webinst");
        unlink($contentTar . ".tar.gz");
        unset($pharTar);
        unlink($this->inputPath . DIRECTORY_SEPARATOR . $this->conf["moduleName"] . ".tar");

        return $outputPath . DIRECTORY_SEPARATOR . $webinstName . ".webinst";
    }

    /**
     * @return string
     */
    public function getWebinstName()
    {
        return $this->templateEngine->render(
            "{{moduleName}}-{{version}}-{{release}}", $this->conf
        );
    }
}
