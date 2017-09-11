<?php

namespace Dcp\DevTools\CreateWorkflow;

use Dcp\DevTools\CreateFamily\CreateFamily;
use Dcp\DevTools\ImportFamily\ImportFamily;
use Dcp\DevTools\Template\WorkflowClass;
use Dcp\DevTools\Utils\FileUtils;
use Dcp\DevTools\Utils\StringUtils;

/**
 * Class CreateFamily
 *
 * @package Dcp\DevTools\CreateWorkflow
 */
class CreateWorkflow extends CreateFamily
{
    public function create()
    {
        $tmpDir = sys_get_temp_dir() . '/dcp_family' . uniqid($this->options['name'], true);
        if (!@mkdir($tmpDir) && !is_dir($tmpDir)) {
            throw new Exception('could not create temp dir');
        }

        $this->createClass($tmpDir);
        $this->createStruct($tmpDir);
        $this->createParam($tmpDir);
        $this->createConfig($tmpDir);
        $this->createDescription($tmpDir);

        $importFamily = new ImportFamily($this->options);
        $actionLog = $importFamily->importFamilyFiles($tmpDir);

        FileUtils::recursiveRm($tmpDir, true);

        return $actionLog;
    }

    protected function createClass($tmpDir)
    {
        $renderOptions = [
            'namespace' => $this->options['namespace'],
            'name' => $this->options['name'],
            'parent' => $this->getParent()
        ];

        $familyClass = new WorkflowClass();
        $familyClass->render($renderOptions, $tmpDir);

        $this->contentDescription['Class'] = StringUtils::normalizeClassName(strtolower($this->options["name"]))
            . '.php';
    }

    protected function createWorkflow($tmpDir)
    {
        //noop
        return [
            'outputDir' => $this->options['outputDir'],
            'infoXmlPath' => $this->options['sourcePath'],
            'infoXmlPathName' => $this->options['sourcePath'] . '/info.xml',
            'skippedFiles' => [],
            'overwrittenFiles' => [],
            'backupDir' => '',
            'importedPhpFileNames' => [],
            'importedCsvFileNames' => [],
            'importedImgFileNames' => [],
            'installProcessAdded' => [],
            'upgradeProcessAdded' => []
        ];
    }

    protected function createDescription($tmpDir)
    {
        $filename = sprintf("%s/%s__DESC.json", $tmpDir, $this->options['name']);
        if (false === file_put_contents($filename, json_encode($this->contentDescription, JSON_PRETTY_PRINT))) {
            throw new Exception("cannot write $filename");
        }
    }

    protected function getParent()
    {
        return $this->options['parent'] ?? 'WDOC';
    }

    protected function getUseFor()
    {
        return 'SW';
    }
}