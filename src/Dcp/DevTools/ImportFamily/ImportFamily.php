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

