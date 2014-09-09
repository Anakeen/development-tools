<?php
namespace dcp\DevTools\Template;

class Template {

    protected $templateBaseDir;
    protected $templates = array();
    protected $logicalNameRegExp = "/^[A-Za-z]+[A-Za-z_]*$/";

    public function __construct() {
        $templateBaseDir = dirname(__FILE__)
            . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . 'templates';

        $realDir = realpath($templateBaseDir);
        if (is_dir($realDir)) {
            $templateBaseDir = $realDir;
        }
        $this->templateBaseDir = $templateBaseDir;

        return $this;
    }

    public function getTemplate($templateName) {
        if (isset($this->templates[$templateName])) {
            return $this->templates[$templateName];
        }
        $fileTemplate = $this->templateBaseDir . DIRECTORY_SEPARATOR . $templateName . ".mustache";

        if (!is_file($fileTemplate)) {
            throw new Exception("Unable to find $templateName");
        }
        $this->templates[$templateName] = $fileTemplate;
        return $this->templates[$templateName];
    }

    public function render($templateName, $arguments, $outputPath = false, $force = false) {
        $mustacheEngine = new \Mustache_Engine;
        $templatePath = $this->getTemplate($templateName);

        $render = $mustacheEngine->render(file_get_contents($templatePath), $arguments);
        if (is_file($outputPath) && $force === false) {
            throw new Exception("The file $outputPath exist : try with force option if you want overwrite it");
        }
        if ($outputPath) {
            $return = file_put_contents($outputPath, $render);
            if ($return === false) {
                throw new Exception("Unable to write in ".$outputPath);
            }
        }
        return $render;
    }

    public function checkLogicalName($name) {
        return preg_match($this->logicalNameRegExp, $name) === 1;
    }

} 