<?php

namespace Dcp\DevTools\CreateFamily;

use Dcp\DevTools\CreateWorkflow\CreateWorkflow;
use Dcp\DevTools\ImportFamily\ImportFamily;
use Dcp\DevTools\Template\FamilyClass;
use Dcp\DevTools\Utils\ConfigFile;
use Dcp\DevTools\Utils\FileUtils;
use Dcp\DevTools\Utils\StringUtils;

/**
 * Class CreateFamily
 *
 * @package Dcp\DevTools\CreateFamily
 */
class CreateFamily
{
    protected $contentDescription = [];

    public function __construct(array $options = [])
    {
        $missingOptions = [];
        if (!isset($options['name'])) {
            $missingOptions['name'] = 'family name is required';
        }
        if (!isset($options['outputDir'])) {
            $missingOptions['outputDir'] = 'outputDir is required';
        }
        if (!isset($options['sourcePath'])) {
            $missingOptions['sourcePath'] = 'sourcePath is required';
        }

        if (0 < count($missingOptions)) {
            throw new Exception(
                sprintf(
                    "Missing options:\n%s",
                    '  - ' . implode("\n  - ", $missingOptions)
                )
            );
        }

        $this->options = $options;

        $this->config = new ConfigFile($options['sourcePath']);

        $csvParam = $this->config->get('csvParam', [
            'enclosure' => '"',
            'delimiter' => ';'
        ], ConfigFile::GET_MERGE_DEFAULTS);

        $this->options['enclosure'] = $csvParam['enclosure'];
        $this->options['delimiter'] = $csvParam['delimiter'];

        //inject options fo importFamily Class
        if (!isset($this->options['url'])) {
            $this->options['url'] = '';
        }
        if (!isset($this->options['port'])) {
            $this->options['port'] = '';
        }

        $this->contentDescription['name'] = $this->options['name'];
        $this->contentDescription['parent'] = $this->getParent();
    }

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
        $wfActionLog = $this->createWorkflow($tmpDir);
        $this->createDescription($tmpDir);

        $importFamily = new ImportFamily($this->options);
        $actionLog = $importFamily->importFamilyFiles($tmpDir);

        FileUtils::recursiveRm($tmpDir, true);

        if (is_array($wfActionLog)) {
            if (!empty($wfActionLog['overwrittenFiles'])
                && is_array($wfActionLog['overwrittenFiles'])
            ) {
                $actionLog['overwrittenFiles'] = array_merge(
                    $actionLog['overwrittenFiles'],
                    $wfActionLog['overwrittenFiles']
                );
            }
            if (!empty($wfActionLog['importedPhpFileNames'])
                && is_array($wfActionLog['importedPhpFileNames'])
            ) {
                foreach ($wfActionLog['importedPhpFileNames'] as $fileType => $file) {
                    $actionLog['importedPhpFileNames']['WF_' . $fileType] = $file;
                }
            }
            if (!empty($wfActionLog['importedCsvFileNames'])
                && is_array($wfActionLog['importedCsvFileNames'])
            ) {
                foreach ($wfActionLog['importedCsvFileNames'] as $fileType => $file) {
                    $actionLog['importedCsvFileNames']['WF_' . $fileType] = $file;
                }
            }
            if (!empty($wfActionLog['importedImgFileNames'])
                && is_array($wfActionLog['importedImgFileNames'])
            ) {
                foreach ($wfActionLog['importedImgFileNames'] as $fileType => $file) {
                    $actionLog['importedImgFileNames']['WF_' . $fileType] = $file;
                }
            }
            if (!empty($wfActionLog['installProcessAdded'])
                && is_array($wfActionLog['installProcessAdded'])
            ) {
                $actionLog['installProcessAdded'] = array_merge(
                    $actionLog['installProcessAdded'],
                    $wfActionLog['installProcessAdded']
                );
            }
            if (!empty($wfActionLog['upgradeProcessAdded'])
                && is_array($wfActionLog['upgradeProcessAdded'])
            ) {
                $actionLog['upgradeProcessAdded'] = array_merge(
                    $actionLog['upgradeProcessAdded'],
                    $wfActionLog['upgradeProcessAdded']
                );
            }
        }

        //move wf backup into family backup
        if (!empty($wfActionLog['backupDir'])
            && is_dir($wfActionLog['backupDir'])
            && !empty($actionLog['backupDir'])
            && is_dir($actionLog['backupDir'])
        ) {
            FileUtils::recursiveCp($wfActionLog['backupDir'], $actionLog['backupDir']);
            FileUtils::recursiveRm($wfActionLog['backupDir'], true);
        }

        return $actionLog;
    }

    protected function createClass($tmpDir)
    {
        $renderOptions = [
            'namespace' => $this->options['namespace'],
            'name' => $this->options['name']
        ];
        $parent = $this->getParent();
        if (!empty($parent)) {
            $renderOptions['parent'] = $parent;
        }

        $familyClass = new FamilyClass();
        $familyClass->render($renderOptions, $tmpDir);

        $this->contentDescription['Class'] = StringUtils::normalizeClassName(strtolower($this->options["name"]))
            . '.php';
    }

    protected function createStruct($tmpDir)
    {
        $parent = $this->getParent();

        $filename = sprintf("%s/%s__STRUCT.csv", $tmpDir, $this->options['name']);
        $data = [];
        $data[] = ['//BEGIN', 'Parent family', '', '', '', 'Family name'];
        $data[] = ['BEGIN', $parent, '', '', '', $this->options['name']];
        $data[] = ['', '', '', '', '', ''];
        $data[] = ['', '', '', '', '', ''];
        $data[] = ['END', '', '', '', '', ''];
        $this->putcsv($filename, $data);

        $this->contentDescription['Struct'] = basename($filename);
    }

    protected function createParam($tmpDir)
    {
        $parent = $this->getParent();

        $filename = sprintf("%s/%s__PARAM.csv", $tmpDir, $this->options['name']);
        $data = [];
        $data[] = ['//BEGIN', 'Parent family', '', '', '', 'Family name'];
        $data[] = ['BEGIN', $parent, '', '', '', $this->options['name']];
        $data[] = ['', '', '', '', '', ''];
        $data[] = ['', '', '', '', '', ''];
        $data[] = ['', '', '', '', '', ''];
        $data[] = ['//DEFAULT', 'Id', 'Default Value', '', '', ''];
        $data[] = ['', '', '', '', '', ''];
        $data[] = ['', '', '', '', '', ''];
        $data[] = ['//INITIAL', 'Id', 'initial Value', '', '', ''];
        $data[] = ['', '', '', '', '', ''];
        $data[] = ['', '', '', '', '', ''];
        $data[] = ['END', '', '', '', '', ''];
        $this->putcsv($filename, $data);

        $this->contentDescription['Param'] = basename($filename);
    }

    protected function createConfig($tmpDir)
    {
        $parent = $this->getParent();
        $name = $this->options['name'];
        $title = $this->getTitle();
        $icon = $this->getIcon();
        $className = $this->getClassName();
        $usefor = $this->getUseFor();

        $filename = sprintf("%s/%s__CONFIG.csv", $tmpDir, $name);
        $data = [];
        $data[] = ['//BEGIN', 'Parent family', 'Family title', '', '', 'Family name'];
        $data[] = ['BEGIN', $parent, $title, '', '', $name];
        $data[] = ['ICON', $icon, '', '', '', ''];
        $data[] = ['CLASS', $className, '', '', '', ''];
        $data[] = ['USEFOR', $usefor, '', '', '', ''];
        $data[] = ['', '', '', '', '', ''];
        $data[] = ['END', '', '', '', '', ''];
        $this->putcsv($filename, $data);

        $this->contentDescription['Config'] = basename($filename);
    }

    protected function createWorkflow($tmpDir)
    {
        if (isset($this->options['workflow'])) {
            $name = $this->options['name'];
            $parent = $this->getParent();
            $wfamName = 'WFAM_' . $name;
            $wdocName = 'WDOC_' . $name;
            $title = $this->getTitle() ?: $this->options['name'];
            $wfamTitle = 'WFAM pour ' . ($title);
            $wdocTitle = 'WDOC pour ' . ($title);

            $wfamRenderOptions = [
                'name' => $wfamName,
                'outputDir' => $this->options['outputDir'],
                'sourcePath' => $this->options['sourcePath'],
                'namespace' => $this->options['namespace'],
                'title' => $wfamTitle
            ];
            if (!empty($this->options['no-backup'])) {
                $wfamRenderOptions['no-backup'] = $this->options['no-backup'];
            }

            $workflowCreator = new CreateWorkflow($wfamRenderOptions);
            $actionLogs = $workflowCreator->create();


            $filename = sprintf("%s/%s__WORKFLOW.csv", $tmpDir, $name);
            $data = [];
            $data[] = ['//FAM', $wfamName, '<specid>', '<fldid>', 'Titre', 'Description', 'famille', 'famille (titre)'];
            $data[] = ['ORDER', $wfamName, '', '', 'ba_title', 'wf_desc', 'wf_famid', 'wf_fam'];
            $data[] = ['DOC', $wfamName, $wdocName, '', $wdocTitle, '', $name, $title];
            $data[] = ['', '', '', '', '', '', '', ''];
            $data[] = ['//BEGIN', 'Parent family', 'Family title', '', '', 'Family name', '', ''];
            $data[] = ['BEGIN', $parent, '', '', '', $name, '', ''];
            $data[] = ['WID', $wdocName, '', '', '', '', '', ''];
            $data[] = ['', '', '', '', '', '', '', ''];
            $data[] = ['END', '', '', '', '', '', '', ''];
            $this->putcsv($filename, $data);

            $this->contentDescription['Workflow'] = basename($filename);
            $this->contentDescription['wfam'] = $wfamName;

            return $actionLogs;
        }
        return [];
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
        return $this->options['parent'] ?? '';
    }

    protected function getTitle()
    {
        return $this->options['title'] ?? '';
    }

    protected function getIcon()
    {
        return $this->options['icon'] ?? $this->options['name'] . '.png';
    }

    protected function getClassName()
    {
        return $this->options['namespace'] . '\\' . StringUtils::normalizeClassName(strtolower($this->options["name"]));
    }

    protected function getUseFor()
    {
        return 'S';
    }

    protected function putcsv($filename, Array $data)
    {
        $handler = fopen($filename, "wb");
        if (!$handler) {
            throw new Exception(sprintf("Cannot open \"%s\" to write csv", $filename));
        }

        foreach ($data as $row) {
            fputcsv($handler, $row, $this->options['delimiter'], $this->options['enclosure']);
        }
        fclose($handler);
    }
}