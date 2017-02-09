<?php

namespace Dcp\DevTools\ImportFamily;

/**
 * Class ImportFamily
 * @package Dcp\DevTools\ImportFamily
 */
class ImportFamily
{
    protected $options;

    public function __construct(array $options = [])
    {
        $missingOptions = [];
        $invalidOperands = [];

        if (!isset($options['url'])) {
            $missingOptions['url'] = 'You must provide an url';
        }
        if (!isset($options['port'])) {
            $missingOptions['port'] = 'You must provide a port';
        }
        if (!isset($options['familyPath'])) {
            $missingOptions['familyPath'] = 'You must provide the path to the directory where to import the family';
        }

        if (!isset($options['additional_args']) ||
            count($options['additional_args']) > 1
        ) {
            $invalidOperands['familyName'] = 'You must provide one and only one family name';
        }

        if (0 < count($missingOptions)) {
            throw new Exception(
                sprintf(
                    'Missing options:\n%s',
                    '  - ' . implode('\n  - ', $missingOptions)
                )
            );
        }

        if (0 < count($invalidOperands)) {
            throw new Exception(
                sprintf(
                    'Invalid operands:\n%s',
                    '  - ' . implode('\n  - ', $invalidOperands)
                )
            );
        }

        $this->options = $options;
        $this->options['family'] = $options['additional_args'][0];
        $this->options['familyPath'] = realpath($this->options['familyPath']);
    }

    /**
     * Download the zip file from
     * {{ $this->options['url'] }}/admin.php
     * ?app=DCPDEVEL&action=EXPORTFAMILYCONFIG&family={{ $this->options['family'] }}
     * The file is then unzipped in a temporary folder.
     * The csv files are moved to $this->options['familyPath'].
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
        $tempDirPathName = uniqid(sys_get_temp_dir() . '/import', true);
        $tempFilePathName = tempnam($tempDirPathName, 'imp');
        $tempFile = fopen($tempFilePathName, 'w+');

        $request = curl_init();

        try {
            $data = [
                'app' => 'DCPDEVEL',
                'action' => 'EXPORTFAMILYCONFIG',
                'family' => $this->options['family']
            ];

            curl_setopt_array(
                $request,
                [
                    CURLOPT_URL => $this->options['url'] . '/admin.php',
                    CURLOPT_PORT => $this->options['port'],
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                    CURLOPT_POSTFIELDS => $data,
                    CURLOPT_FILE => $tempFile
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
            } elseif (403 === $httpCode) {
                throw new Exception(
                    'invalid credentials'
                );
            } elseif (299 < $httpCode) {
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
            fclose($tempFile);
            throw $e;
        }

        curl_close($request);
        fclose($tempFile);

        $importedFileNames = [];
        try {
            $zipArchive = new \ZipArchive();
            $res = $zipArchive->open($tempFilePathName);
            if ($res === true) {
                for ($i = 0; $i < $zipArchive->numFiles; $i++) {
                    $importedFileNames[] = $zipArchive->getNameIndex($i);
                }
                $zipArchive->extractTo($tempDirPathName);
                $zipArchive->close();
            } else {
                throw new Exception(
                    'Failed to unzip file ' . $tempFilePathName .
                    ' in directory ' . $tempDirPathName . '.'
                );
            }
        } catch (Exception $e) {
            $zipArchive->close();
            throw $e;
        }

        unlink($tempFilePathName);

        $infoXmlPath = $this->parentDirPathContaining($this->options['familyPath'], 'info.xml');

        if ($infoXmlPath == "") {
            throw new Exception("The module's info.xml file cannot be found, please check the --familyPath option.");
        }

        $csvFileNames = $this->stringsWithSuffix($importedFileNames, 'csv');
        $pngFileNames = $this->stringsWithSuffix($importedFileNames, 'png');

        foreach ($csvFileNames as $fileName) {
            rename(
                $tempDirPathName . '/' . $fileName,
                $this->options['familyPath'] . '/' . $fileName
            );
        }

        foreach ($pngFileNames as $fileName) {
            rename(
                $tempDirPathName . '/' . $fileName,
                $infoXmlPath . '/Images/' . $fileName
            );
        }

        $importedInfoXmlPathName = glob($tempDirPathName . '/*.xml')[0];

        $processAdded = $this->addProcessToInfoXml($infoXmlPath, $importedInfoXmlPathName);

        $this->rmrf($tempDirPathName);


        return [
            'familyPath' => $this->options['familyPath'],
            'infoXmlPath' => $infoXmlPath,
            'infoXmlPathName' => $infoXmlPath . '/info.xml',
            'importedCvsFileNames' => $csvFileNames,
            'importedPngFileNames' => $pngFileNames,
            'installProcessAdded' => $processAdded['installProcessAdded'],
            'upgradeProcessAdded' => $processAdded['upgradeProcessAdded'],
        ];
    }

    /**
     * Add the <post-install> and <post-upgrade> DomNodes
     * from $importedInfoXmlPathName
     * to the info.xml located in $infoXmlPath, only if they don't already exist.
     *
     * @param string $infoXmlPath The directory absolute path
     * where the module's info.xml is located.
     * @param string $importedInfoXmlPathName The absolute path name
     * of the imported .xml file.
     * @return string[]
     * The string representation of the process nodes which have been added to
     * info.xml.
     * @throws Exception
     */
    public function addProcessToInfoXml($infoXmlPath, $importedInfoXmlPathName)
    {
        $pathFromModuleToFamily = substr($this->options['familyPath'], strlen($infoXmlPath) + 1, -1);

        $infoXmlDom = new \DOMDocument();
        $infoXmlDom->formatOutput = true;
        $infoXmlDom->preserveWhiteSpace = false;
        $res = $infoXmlDom->load($infoXmlPath . '/info.xml');

        if ($res === false) {
            throw new Exception('Failed to load ' . $infoXmlPath . '/info.xml' . ' as XML document.');
        }

        $importedInfoXmlDom = new \DOMDocument();
        $res = $importedInfoXmlDom->load($importedInfoXmlPathName);

        if ($res === false) {
            throw new Exception('Failed to load ' . $infoXmlPath . '/info.xml' . ' as XML document.');
        }

        /**@var $postInstallNode \DOMElement * */
        /**@var $importedPostInstallNode \DOMElement * */
        /**@var $postUpgradeNode \DOMElement * */
        /**@var $importedPostUpgradeNode \DOMElement * */

        $postInstallNode = $infoXmlDom->getElementsByTagName('post-install')[0];
        $postInstallProcess = $postInstallNode->getElementsByTagName('process');

        $importedPostInstallNode = $importedInfoXmlDom->getElementsByTagName('post-install')[0];
        $importedPostInstallProcess = $importedPostInstallNode->getElementsByTagName('process');

        $postUpgradeNode = $infoXmlDom->getElementsByTagName('post-upgrade')[0];
        $postUpgradeProcess = $postUpgradeNode->getElementsByTagName('process');

        $importedPostUpgradeNode = $importedInfoXmlDom->getElementsByTagName('post-upgrade')[0];
        $importedPostUpgradeProcess = $importedPostUpgradeNode->getElementsByTagName('process');

        $newInstallProcess = $this->newDomNodesByAttribute($postInstallProcess, $importedPostInstallProcess, 'id');
        $newUpgradeProcess = $this->newDomNodesByAttribute($postUpgradeProcess, $importedPostUpgradeProcess, 'id');

        $this->domAttributesStrReplace($importedPostInstallProcess, 'command', './@APPNAME@', $pathFromModuleToFamily);
        $this->domAttributesStrReplace($importedPostUpgradeProcess, 'command', './@APPNAME@', $pathFromModuleToFamily);

        $this->appendForeignDomNodesTo($infoXmlDom, $postInstallNode, $newInstallProcess);
        $this->appendForeignDomNodesTo($infoXmlDom, $postUpgradeNode, $newUpgradeProcess);

        $res = $infoXmlDom->save($infoXmlPath . '/info.xml');

        if ($res === false) {
            throw new Exception('Failed to save ' . $infoXmlPath . '/info.xml' . ' as XML document.');
        }

        return [
            'installProcessAdded' => $this->domNodesToStrings($newInstallProcess),
            'upgradeProcessAdded' => $this->domNodesToStrings($newUpgradeProcess)
        ];
    }

    /**
     * Starting from $initialDirPath directory,
     * the function goes back to the parent directory,
     * until it reach a directory containing a file named $fileName.
     * If it does, it returns the directory absolute path where $fileName is located.
     * If it reach the root directory without finding $fileName it returns "".
     *
     * @param string $initialDirPath
     * The directory absolute path from where to start searching for
     * the file named $fileName.
     * @param string $fileName The name of the file to search for.
     * @return string
     * The directory absolute path where $fileName is located.
     * "" if $fileName cannot be found.
     */
    public function parentDirPathContaining($initialDirPath, $fileName)
    {
        $parentDirPath = $initialDirPath;
        while (count(glob($parentDirPath . '/' . $fileName)) < 1) {
            if (strlen($parentDirPath) == 1) {
                return "";
            }
            $parentDirPath = dirname($parentDirPath);
        }

        return $parentDirPath;
    }

    /**
     * Equivalent to rm -rf $filePathName on *nix.
     * Delete the file and all its sub-directory / files if it's a
     * directory.
     *
     * @param string $filePathName
     */
    public function rmrf($filePathName)
    {
        if (is_dir($filePathName)) {
            $files = array_diff(scandir($filePathName), ['.', '..']);
            foreach ($files as $file) {
                $this->rmrf($filePathName . "/" . $file);
            }
        } else {
            unlink($filePathName);
            return;
        }
        rmdir($filePathName);
    }

    /**
     * Returns the DomNodes from $domNodes
     * that are missing from the DomNodes $oldDomNodes,
     * the comparison between nodes is made
     * by the value of the attribute named $attributeName.
     *
     * @param \DOMNodeList
     * $oldDomNodes The Nodes to search for the absence of the $domNodes.
     * @param \DOMNodeList $domNodes The Nodes to be searched in $oldDomNodes.
     * @param string $attributeName The name of the attribute used to compare nodes.
     * @return \DOMNode[] The DomNodes from $domNodes missing from $oldDomNodes.
     */
    public function newDomNodesByAttribute(
        \DOMNodeList $oldDomNodes,
        \DOMNodeList $domNodes,
        $attributeName
    ) {
        $newDomNodes = [];
        for ($i = 0; $i < $domNodes->length; $i++) {
            for ($j = 0; $j < $oldDomNodes->length; $j++) {
                if ($domNodes[$i]->attributes->getNamedItem($attributeName) != null
                    && $oldDomNodes[$j]->attributes->getNamedItem($attributeName) != null
                    && $domNodes[$i]->attributes->getNamedItem($attributeName)->value ==
                    $oldDomNodes[$j]->attributes->getNamedItem($attributeName)->value
                ) {
                    break;
                }
                if ($j == $oldDomNodes->length - 1) {
                    $newDomNodes[] = $domNodes[$i];
                }
            }
        }
        return $newDomNodes;
    }

    /**
     * Replace the string $target with the string $replacement inside the
     * value of the attribute named $attributeName, which is an attribute of the DomNode $domNode.
     *
     * @param \DOMNode $domNode A DomNode having the attribute named $attributeName.
     * @param string $attributeName The name of the attribute you want the value to be changed.
     * @param string $target The string that will be replaced by $replacement.
     * @param string $replacement The string that will replace $target.
     */
    public function domAttributeStrReplace(\DOMNode $domNode, $attributeName, $target, $replacement)
    {
        $domNode->attributes->getNamedItem($attributeName)->nodeValue =
            str_replace($target, $replacement, $domNode->attributes->getNamedItem($attributeName)->nodeValue);
    }

    /**
     * Replace the string $target with the string $replacement inside the
     * value of the attribute named $attributeName, which is an attribute of each nodes
     * from the DomNodeList $domNodes.
     *
     * @param \DOMNodeList $domNodes DomNodes having the attribute named $attributeName.
     * @param string $attributeName The name of the attribute you want the value to be changed.
     * @param string $target The string that will be replaced by $replacement.
     * @param string $replacement The string that will replace $target.
     */
    public function domAttributesStrReplace(\DOMNodeList $domNodes, $attributeName, $target, $replacement)
    {
        for ($i = 0; $i < $domNodes->length; $i++) {
            $this->domAttributeStrReplace($domNodes[$i], $attributeName, $target, $replacement);
        }
    }

    /**
     * Append the DomNode $child to the DomNode $parent.
     * Nodes come from different DomDocuments.
     *
     * @param \DOMDocument $parentDomDocument The DomDocument of the $parent DomNode.
     * @param \DOMNode $parent The DomNode to append the DomNode $child.
     * @param \DOMNode $child The DomNode to be appended to the DomNode $parent.
     */
    public function appendForeignDomNodeTo(\DOMDocument $parentDomDocument, \DOMNode $parent, \DOMNode $child)
    {
        $parent->appendChild($parentDomDocument->importNode($child, true));
    }

    /**
     * Append the DomNodeList $children to the DomNode $parent.
     * Nodes come from different DomDocuments.
     *
     * @param \DOMDocument $parentDomDocument The DomDocument of the $parent DomNode.
     * @param \DOMNode $parent The DomNode to append the DomNodes $children.
     * @param \DOMNode[] $children The DomNodes to be appended to the DomNode $parent.
     */
    public function appendForeignDomNodesTo(\DOMDocument $parentDomDocument, \DOMNode $parent, array $children)
    {
        foreach ($children as $child) {
            $this->appendForeignDomNodeTo($parentDomDocument, $parent, $child);
        }
    }

    /**
     * Return the strings in $strings ending with $suffix.
     *
     * @param string[] $strings The strings to filter.
     * @param string $suffix The ending a string need to have to be in the returned array.
     * @return string[] The strings in $strings ending with $suffix.
     */
    public function stringsWithSuffix(array $strings, $suffix)
    {
        $filteredStrings = [];
        for ($i = 0; $i < count($strings); $i++) {
            if (substr($strings[$i], -strlen($suffix)) == $suffix) {
                $filteredStrings[] = $strings[$i];
            }
        }
        return $filteredStrings;
    }

    /**
     * Return the string representation of each DomNode in the DomNodes $domNodes
     * as an array.
     *
     * @param \DOMNode[] $domNodes The DomNodes to convert to strings.
     * @return string[] The string representation of each DomNode in the DomNodes $domNodes.
     */
    public function domNodesToStrings(array $domNodes)
    {
        $strings = [];
        foreach ($domNodes as $node) {
            $strings[] = $node->C14N();
        }
        return $strings;
    }
}
