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

