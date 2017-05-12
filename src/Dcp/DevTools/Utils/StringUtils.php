<?php

namespace Dcp\DevTools\Utils;

class StringUtils
{
    static function normalizeClassName($className)
    {
        return ucfirst(
            preg_replace_callback(
                '#_+(\w)#',
                function ($matches) {
                    return strtoupper($matches[1]);
                },
                $className
            )
        );
    }
}
