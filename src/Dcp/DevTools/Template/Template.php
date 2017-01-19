<?php

namespace Dcp\DevTools\Template;

class Template
{
    protected $templateBaseDir;
    protected $templates = array();
    protected $logicalNameRegExp = '/^[A-Za-z][A-Za-z0-9_]*$/';
    protected $moduleNameRegExp = '/^[A-Za-z][A-Za-z0-9_-]*$/';

    public function __construct()
    {
        $templateBaseDir = join(DIRECTORY_SEPARATOR, array(__DIR__, '..', '..', '..', '..', 'templates'));

        $realDir = realpath($templateBaseDir);
        if (is_dir($realDir)) {
            $templateBaseDir = $realDir;
        }
        $this->templateBaseDir = $templateBaseDir;

        return $this;
    }

    public function getTemplate($templateName)
    {
        if (isset($this->templates[$templateName])) {
            return $this->templates[$templateName];
        }
        $fileTemplate = $this->templateBaseDir . DIRECTORY_SEPARATOR . $templateName . ".mustache";

        if (!is_file($fileTemplate)) {
            throw new Exception("Unable to find $templateName $fileTemplate");
        }
        $this->templates[$templateName] = $fileTemplate;
        return $this->templates[$templateName];
    }

    public function mainRender($templateName, $arguments, $outputPath = false, $force = false)
    {
        $mustacheEngine = new \Mustache_Engine;
        $templatePath = $this->getTemplate($templateName);

        $render = $mustacheEngine->render(file_get_contents($templatePath), $arguments);
        if (is_file($outputPath) && $force === false) {
            throw new Exception("The file $outputPath exist : try with force option if you want overwrite it");
        }
        if ($outputPath) {
            $return = file_put_contents($outputPath, $render);
            if ($return === false) {
                throw new Exception("Unable to write in " . $outputPath);
            }
        }
        return $render;
    }

    public function checkLogicalName($name)
    {
        return preg_match($this->logicalNameRegExp, $name) === 1;
    }
    public function checkModuleName($name)
    {
        return preg_match($this->moduleNameRegExp, $name) === 1;
    }
}
