<?php

namespace Dcp\DevTools\Po;

use Dcp\DevTools\Template\Template;

class AnalyzeMustache extends Analyze
{
    public function extract(\Iterator $filesPath)
    {
        $keys = [];

        foreach ($filesPath as $mustacheTpl) {
            $extractor = new extractMustache($mustacheTpl);
            $keys = array_merge($keys, $extractor->extractKeys());
        }

        ksort($keys);

        $temporaryFile = tempnam(sys_get_temp_dir(), "mustache_keys_");

        $template = new Template();
        $template->mainRender(
            "temporary_mustache_file",
            [
                "keys" => array_values($keys)
            ],
            $temporaryFile,
            true
        );

        parent::extract(new \ArrayIterator([$temporaryFile]));

        unset($temporaryFile);
    }
}
