<?php

namespace Dcp\DevTools\ImportFamily;

use Dcp\DevTools\Utils\ConfigFile;
use Dcp\DevTools\Utils\FileUtils;

/**
 * Class ImportFamily
 *
 * @package Dcp\DevTools\ImportFamily
 */
class ImportFamily
{
    protected $options;

    public function __construct(array $options = [])
    {
        $missingOptions = [];

        if (!isset($options['url'])) {
            $missingOptions['url'] = 'Context url is required';
        }
        if (!isset($options['port'])) {
            $missingOptions['port'] = 'Context port is required';
        }
        if (!isset($options['name'])) {
            $missingOptions['name'] = 'name is required';
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
    }

    /**
     * Download the zip file from
     * {{ $this->options['url'] }}/admin.php
     * ?app=DCPDEVEL&action=EXPORTFAMILYCONFIG&family={{ $this->options['family'] }}
     * The file is then unzipped in a temporary folder.
     * The csv files are moved to $this->options['outputDir'].
     * The png files are moved to /Images.
     * The post-install and post-upgrade process of the imported .xml
     * are added to the module's info.xml file.
     *
     * @return string[] Logs specifying what files have been imported
     * or modified during the importFamily command.
     * @throws CurlException
     * @throws Exception
     */
    public function importFamily()
    {
        $tmpDir = sys_get_temp_dir() . '/dcp_import' . uniqid($this->options['name'], true);
        if (!@mkdir($tmpDir) && !is_dir($tmpDir)) {
            throw new Exception('could not create temp dir');
        }
        $this->getFamilyFiles($tmpDir);

        $importInfos = $this->importFamilyFiles($tmpDir);

        FileUtils::recursiveRm($tmpDir, true);

        return $importInfos;
    }

    /**
     * @param $tmpDir
     *
     * @throws CurlException
     * @throws Exception
     */
    public function getFamilyFiles($tmpDir)
    {
        $tmpFilePath = $tmpDir . '/' . $this->options['name'] . '.zip';
        $tmpFile = fopen($tmpFilePath, 'w+b');

        if (false === $tmpFile) {
            throw new Exception(sprintf('could not initialize zip file (%s)', $tmpFile));
        }

        $request = curl_init();

        try {
            $data = [
                'app' => 'DCPDEVEL',
                'action' => 'EXPORTFAMILYCONFIG',
                'family' => $this->options['name']
            ];

            /** @noinspection CurlSslServerSpoofingInspection */
            curl_setopt_array(
                $request,
                [
                    CURLOPT_URL => $this->options['url'] . '/admin.php',
                    CURLOPT_PORT => $this->options['port'],
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                    CURLOPT_POSTFIELDS => $data,
                    CURLOPT_FILE => $tmpFile
                ]
            );

            //FIXME: handle auth
            $curl_result = curl_exec($request);

            if (false === $curl_result) {
                throw new Exception(
                    sprintf(
                        'An error occurred during connection to server: %d (%s)',
                        curl_errno($request),
                        curl_error($request)
                    )
                );
            }

            $httpCode = curl_getinfo($request, CURLINFO_HTTP_CODE);
            if (401 === $httpCode) {
                throw new Exception(
                    'Authentication is required'
                );
            }
            if (403 === $httpCode) {
                throw new Exception(
                    'invalid credentials'
                );
            }
            if (299 < $httpCode) {
                throw new Exception(
                    sprintf(
                        '%s returned an error status code: %d',
                        curl_getinfo($request, CURLINFO_EFFECTIVE_URL),
                        $httpCode
                    )
                );
            }
        } catch (CurlException $e) {
            curl_close($request);
            fclose($tmpFile);
            throw $e;
        }

        curl_close($request);
        fclose($tmpFile);

        try {
            $zipArchive = new \ZipArchive();
            if (false === $zipArchive->open($tmpFilePath)) {
                throw new Exception(
                    'Failed to unzip file ' . $tmpFilePath .
                    ' in directory ' . $tmpDir . '.'
                );
            }
            $zipArchive->extractTo($tmpDir);
            $zipArchive->close();
        } catch (Exception $e) {
            $zipArchive->close();
            throw $e;
        }

        unlink($tmpFilePath);
    }


    /**
     * @param $tmpDir
     *
     * @return array
     * @throws Exception
     */
    public function importFamilyFiles($tmpDir)
    {
        if (!is_file(sprintf('%s/%s__DESC.json', $tmpDir, $this->options['name']))) {
            throw new Exception('Family files does not include a description file. Ensure you have up to date dynacase version.');
        }

        $sourcePath = $this->options['sourcePath'];
        $outputDir = $this->options['outputDir'];

        $familyDescription = json_decode(
            file_get_contents(
                sprintf('%s/%s__DESC.json',
                    $tmpDir,
                    $this->options['name']
                )
            ),
            true
        );

        $overwrittenFiles = [];

        if (empty($this->options['no-backup']) || true !== $this->options['no-backup']) {
            $backupPath = $this->backupDir($outputDir);
        } else {
            $backupPath = 'backup disabled';
        }

        $phpFileNames = [];
        foreach (
            [
                'Class'
            ] as $fileType
        ) {
            if (isset($familyDescription[$fileType]) && file_exists($tmpDir . '/' . $familyDescription[$fileType])
            ) {
                $fileName = $familyDescription[$fileType];
                $destFile = $sourcePath . '/' . $outputDir . '/' . $fileName;
                if (file_exists($destFile)) {
                    $overwrittenFiles[] = $fileName;
                }
                rename(
                    $tmpDir . '/' . $fileName,
                    $destFile
                );
                $phpFileNames[$fileType] = $outputDir . '/' . $fileName;
            }
        }

        $csvFileNames = [];
        foreach (
            [
                'Struct',
                'Param',
                'Config',
                'Workflow',
                'Others'
            ] as $fileType
        ) {
            if (isset($familyDescription[$fileType]) && file_exists($tmpDir . '/' . $familyDescription[$fileType])
            ) {
                $fileName = $familyDescription[$fileType];
                $destFile = $sourcePath . '/' . $outputDir . '/' . $fileName;
                if (file_exists($destFile)) {
                    $overwrittenFiles[] = $fileName;
                }
                rename(
                    $tmpDir . '/' . $fileName,
                    $destFile
                );
                $csvFileNames[$fileType] = $outputDir . '/' . $fileName;
            }
        }

        $imgFilenames = [];
        if (isset($familyDescription['Icon']) && file_exists($tmpDir . '/' . $familyDescription['Icon'])
        ) {
            if (!is_dir($sourcePath . '/Images')) {
                if (!@mkdir($sourcePath . '/Images')
                    && !is_dir($sourcePath . '/Images')
                ) {
                    throw new Exception('could not create Images dir');
                }
            }
            $fileName = $familyDescription['Icon'];
            $destFile = $sourcePath . '/Images/' . $familyDescription['Icon'];
            if (file_exists($destFile)) {
                $overwrittenFiles[] = $fileName;
            }
            rename(
                $tmpDir . '/' . $fileName,
                $destFile
            );
            $imgFilenames['Icon'] = '/Images/' . $fileName;
        }

        $infoXmlUpdates = $this->updateInfoXml($familyDescription);

        if (empty($this->options['no-backup'])) {
            $this->cleanBackup($backupPath, $overwrittenFiles);
        }

        return [
            'outputDir' => $outputDir,
            'infoXmlPath' => $sourcePath,
            'infoXmlPathName' => $sourcePath . '/info.xml',
            'overwrittenFiles' => $overwrittenFiles,
            'backupDir' => $backupPath,
            'importedPhpFileNames' => $phpFileNames,
            'importedCsvFileNames' => $csvFileNames,
            'importedImgFileNames' => $imgFilenames,
            'installProcessAdded' => $infoXmlUpdates['installProcessAdded'],
            'upgradeProcessAdded' => $infoXmlUpdates['upgradeProcessAdded'],
        ];
    }

    public function updateInfoXml($familyDescription)
    {
        $infoXmlUpdates = [];

        $sourcePath = $this->options['sourcePath'];
        $infoXml = $sourcePath . DIRECTORY_SEPARATOR . 'info.xml';

        $infoXmlDom = new \DOMDocument();
        $infoXmlDom->formatOutput = true;
        $infoXmlDom->preserveWhiteSpace = false;
        if (false === $infoXmlDom->load($infoXml)) {
            throw new Exception('Failed to load ' . $infoXml . ' as XML document.');
        }

        $csvParam = $this->config->get('csvParam', [
            'enclosure' => '"',
            'delimiter' => ';'
        ], ConfigFile::GET_MERGE_DEFAULTS);

        $options = [
            'csvEnclosure' => '"' === $csvParam['enclosure'] ? "'" . $csvParam['enclosure'] . "'"
                : '"' . $csvParam['enclosure'] . '"',
            'csvSeparator' => $csvParam['delimiter']
        ];

        /**@var $postInstallNode \DOMElement * */
        $postInstallNode = $infoXmlDom->getElementsByTagName('post-install')[0];
        $infoXmlUpdates['installProcessAdded'] = $this->addInfoXmlImportProcess(
            $familyDescription,
            $postInstallNode,
            [
                'Config',
                'Struct',
                'Param',
                'Workflow',
                'Others'
            ],
            $options
        );

        /**@var $postUpgradeNode \DOMElement * */
        $postUpgradeNode = $infoXmlDom->getElementsByTagName('post-upgrade')[0];
        $infoXmlUpdates['upgradeProcessAdded'] = $this->addInfoXmlImportProcess(
            $familyDescription,
            $postUpgradeNode,
            ['Struct'],
            $options
        );

        if (false === $infoXmlDom->save($infoXml)) {
            throw new Exception('Failed to save ' . $infoXml . ' as XML document.');
        }

        return $infoXmlUpdates;
    }

    /**
     * @param $familyDescription array
     * @param $parentNode        \DOMElement
     * @param $fileTypes         array
     * @param $options           array
     *
     * @return array
     */
    protected function addInfoXmlImportProcess(
        $familyDescription,
        \DOMElement $parentNode,
        Array $fileTypes,
        Array $options
    ) {
        $newProcesses = [];

        $outputDir = $this->options['outputDir'];

        /** @var \DOMElement[] $processNodes */
        $processNodes = $parentNode->getElementsByTagName('process');

        $previousSibling = null;

        if (!empty($familyDescription['parent'])) {
            $patternParentFamily = sprintf('#^./wsh.php\s+--api=importDocuments.+/%s.*?$#',
                $familyDescription['parent']
            );
        } else {
            $patternParentFamily = false;
        }
        if (!empty($familyDescription['wfam'])) {
            $patternWfamFamily = sprintf('#^./wsh.php\s+--api=importDocuments.+/%s.*?$#',
                $familyDescription['wfam']
            );
        } else {
            $patternWfamFamily = false;
        }

        foreach ($processNodes as $processNode) {
            $command = $processNode->getAttribute('command');
            if (false !== $patternParentFamily && preg_match($patternParentFamily, $command)) {
                $previousSibling = $processNode;
            }
            if (false !== $patternWfamFamily && preg_match($patternWfamFamily, $command)) {
                $previousSibling = $processNode;
            }
        }

        foreach ($fileTypes as $fileType) {
            $sourcePath = $this->options['sourcePath'];

            if (isset($familyDescription[$fileType])
                && file_exists($sourcePath . DIRECTORY_SEPARATOR . $outputDir . DIRECTORY_SEPARATOR .
                    $familyDescription[$fileType])
            ) {
                $fileName = $familyDescription[$fileType];

                $nodeFound = false;
                foreach ($processNodes as $processNode) {
                    $command = $processNode->getAttribute('command');
                    $patternCurrentFamily = sprintf('#^./wsh.php\s+--api=importDocuments.+/%s(["\'\s].*)?$#',
                        $fileName
                    );
                    if (preg_match($patternCurrentFamily, $command)) {
                        $nodeFound = true;
                        break;
                    }
                }
                if ($nodeFound) {
                    /** @noinspection PhpUndefinedVariableInspection */
                    $previousSibling = $processNode;
                } else {
                    /** @var \DOMElement $currentProcessNode */
                    $currentProcessNode = $parentNode->ownerDocument->createElement('process');
                    $currentProcessNode->setAttribute('command',
                        sprintf("./wsh.php --api=importDocuments --file='%s' --csv-separator='%s' --csv-enclosure=%s",
                            $outputDir . DIRECTORY_SEPARATOR . $fileName,
                            $options['csvSeparator'],
                            $options['csvEnclosure']
                        )
                    );
                    $labelNode = $parentNode->ownerDocument->createElement(
                        'label',
                        sprintf('Import %s of %s',
                            $fileType,
                            $this->options['name']
                        )
                    );
                    $currentProcessNode->appendChild($labelNode);
                    if (null === $previousSibling) {
                        $previousSibling = $parentNode->appendChild($currentProcessNode);
                    } else {
                        $previousSibling = $parentNode->insertBefore($currentProcessNode,
                            $previousSibling->nextSibling);
                    }

                    $newProcesses[$fileType] = $currentProcessNode->C14N();
                }
            }
        }
        return $newProcesses;
    }

    protected function backupDir($path)
    {
        $backupRootDir = $this->options['sourcePath'] . DIRECTORY_SEPARATOR . 'Backup';
        if (!is_dir($backupRootDir)) {
            if (!@mkdir($backupRootDir)
                && !is_dir($backupRootDir)
            ) {
                throw new Exception('could not create Backup root dir ' . $backupRootDir);
            }
        }

        $backupDir = $backupRootDir . DIRECTORY_SEPARATOR . strftime('%F_%H-%M-%S.') . uniqid();
        if (!is_dir($backupDir)) {
            if (!@mkdir($backupDir)
                && !is_dir($backupDir)
            ) {
                throw new Exception('could not create Backup dir' . $backupDir);
            }
        }

        if (false === FileUtils::recursiveCp($this->options['sourcePath'] . DIRECTORY_SEPARATOR . $path, $backupDir)) {
            throw new Exception("could not copy $path to $backupDir");
        }

        return $backupDir;
    }

    protected function cleanBackup($backupDir, Array $overwrittenFiles)
    {
        if (0 === count($overwrittenFiles)) {
            FileUtils::recursiveRm($backupDir, true);
        } else {
            $i = new \DirectoryIterator($backupDir);
            foreach ($i as $file) {
                $filename = $file->getFilename();
                if ('.' !== $filename && '..' !== $filename && !in_array($filename, $overwrittenFiles)) {
                    if ($file->isDir()) {
                        rmdir($file->getPathname());
                    } else {
                        unlink($file->getPathname());
                    }
                }
            }
        }
    }
}
