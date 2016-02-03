<?php

namespace Dcp\DevTools\Po;


class extractPhp
{
    protected $tokens;
    protected $file;

    public function __construct($file)
    {
        if (!file_exists($file) || !is_readable($file)) {
            throw new \Exception("$file does not exists or is not readable");
        }
        $this->file = $file;
        $this->tokens = token_get_all(file_get_contents($file));
    }

    public function extractSearchLabels()
    {
        $keys = [];

        //Start with a blank namespace and class
        $namespace = $class = "";

        //Set helper values to know that we have found the namespace/class token
        //and need to collect the string values after them
        $gettingNamespace = $gettingClass = false;

        foreach ($this->tokens as $token) {
            if (is_array($token)) {

                //If this token is the namespace declaring,
                //then flag that the next tokens will be the namespace name
                if ($token[0] == T_NAMESPACE) {
                    $gettingNamespace = true;
                }

                //If this token is the class declaring,
                //then flag that the next tokens will be the class name
                if ($token[0] == T_CLASS) {
                    $gettingClass = true;
                }

                //While we're grabbing the namespace name...
                if ($gettingNamespace === true) {

                    //If the token is a string or the namespace separator...
                    if ($token[0] == T_STRING || $token[0] == T_NS_SEPARATOR) {
                        //Append the token's value to the name of the namespace
                        $namespace .= $token[1];

                    }
                }

                //While we're grabbing the class name...
                //If the token is a string, it's the name of the class
                if ($gettingClass && $token[0] == T_STRING) {
                    //Store the token's value as the class name
                    $class = $token[1];
                    $gettingClass = false;
                }

                if ($token[0] == T_DOC_COMMENT) {
                    $matchesKeys = [];
                    if (preg_match(
                        "/.*@(searchLabel)\\s+(?<key>[^\\n]+)\\n.*/s",
                        $token[1], $matchesKeys
                    )) {
                        $matchesTypes = [];
                        $types = ['all'];
                        if (preg_match_all(
                            "/.*@(searchType)\\s+(?<type>[^\\n]+)\\n.*/s",
                            $token[1], $matchesTypes
                        )) {
                            $types = $matchesTypes['type'];
                        }
                        $keys[$matchesKeys["key"]] = [
                            'key' => $matchesKeys["key"],
                            'types' => implode(',', $types),
                            'file' => basename($this->file),
                            'line' => $token[2],
                            'class' => ($namespace ? $namespace . '\\' : '') . ($class ? $class : '')
                        ];
                    }
                }
            } else {
                if ($gettingNamespace && $token === ';') {
                    //If the token is the semicolon, then we're done with the namespace declaration
                    $gettingNamespace = false;
                }
            }
        }
        return $keys;
    }

    public function extractSharpLabels()
    {
        $keys = [];

        //Start with a blank namespace and class
        $namespace = $class = "";

        //Set helper values to know that we have found the namespace/class token
        //and need to collect the string values after them
        $gettingNamespace = $gettingClass = false;

        foreach($this->tokens as $token) {
            if (is_array($token)) {

                //If this token is the namespace declaring,
                //then flag that the next tokens will be the namespace name
                if ($token[0] == T_NAMESPACE) {
                    $gettingNamespace = true;
                }

                //If this token is the class declaring,
                //then flag that the next tokens will be the class name
                if ($token[0] == T_CLASS) {
                    $gettingClass = true;
                }

                //While we're grabbing the namespace name...
                if ($gettingNamespace === true) {
                    //If the token is a string or the namespace separator...
                    if ($token[0] == T_STRING || $token[0] == T_NS_SEPARATOR) {
                        //Append the token's value to the name of the namespace
                        $namespace .= $token[1];

                    }
                }

                //While we're grabbing the class name...
                //If the token is a string, it's the name of the class
                if ($gettingClass && $token[0] == T_STRING) {
                    //Store the token's value as the class name
                    $class = $token[1];
                    $gettingClass = false;
                }

                if ($token[0] == T_DOC_COMMENT && $token[1][0] === '#') {
                    $matches = [];
                    if (preg_match_all(
                        '/\sN?_\("(?<key>[^\)]+)"\)/', $token[1], $matches
                    )) {
                        foreach ($matches["key"] as $key) {
                            $keys[$key] = [
                                'key' => $key,
                                'file' => basename($this->file),
                                'line' => $token[2],
                                'class' => ($namespace ? $namespace . '\\' : '') . ($class ? $class : '')
                            ];
                        }
                    }
                }
            } else {
                if ($gettingNamespace && $token === ';') {
                    //If the token is the semicolon, then we're done with the namespace declaration
                    $gettingNamespace = false;
                }
            }
        }
        if (0 < count($keys)) {
            error_log(
                sprintf(
                    "sharp (#) comments are deprecated. %d found in %s",
                    count($keys),
                    $this->file
                )
            );
        }
        return $keys;
    }

    public function extractWorkflowLabels() {
        $keys = [];

        //Start with a blank namespace, class and constant
        $constName = $namespace = $class = "";

        //Set helper values to know that we have found the namespace/class/const token
        //and need to collect the string values after them
        $gettingConstant = $gettingNamespace = $gettingClass = false;

        foreach ($this->tokens as $token) {
            if (is_array($token)) {

                //If this token is the namespace declaring,
                //then flag that the next tokens will be the namespace name
                if ($token[0] == T_NAMESPACE) {
                    $gettingNamespace = true;
                }

                //If this token is the class declaring,
                //then flag that the next tokens will be the class name
                if ($token[0] == T_CLASS) {
                    $gettingClass = true;
                }

                //While we're grabbing the namespace name...
                if ($gettingNamespace === true) {
                    //If the token is a string or the namespace separator...
                    if ($token[0] == T_STRING || $token[0] == T_NS_SEPARATOR
                    ) {
                        //Append the token's value to the name of the namespace
                        $namespace .= $token[1];

                    }
                }

                //While we're grabbing the class name...
                //If the token is a string, it's the name of the class
                if ($gettingClass && $token[0] == T_STRING) {
                    //Store the token's value as the class name
                    $class = $token[1];
                    $gettingClass = false;
                }

                if ($token[0] != T_WHITESPACE) {
                    //If this token is the const declaring,
                    //then flag that we are in a constant definition context
                    if ($token[0] == T_CONST && $token[1] === 'const') {
                        $gettingConstant = true;
                        $constName = '';
                    } else {
                        //If we have a name
                        if ($gettingConstant && $token[0] == T_STRING) {
                            $gettingConstant = false;
                            $prefix = substr($token[1], 0, 2);
                            //If this is a state constant
                            if ("e_" === $prefix || "s_" === $prefix|| "a_" === $prefix || "t_" === $prefix ) {
                                //store the constant name
                                $constName = $token[1];
                            }
                        } else {
                            //if we have a string
                            if ('' !== $constName && $token[0] == T_CONSTANT_ENCAPSED_STRING) {
                                //store the constant
                                $keys[$constName] = [
                                    'key' => substr($token[1], 1, -1),
                                    'constName' => $constName,
                                    'file' => basename($this->file),
                                    'line' => $token[2],
                                    'class' => ($namespace ? $namespace . '\\' : '') . ($class ? $class : '')
                                ];
                                $constName = '';
                            }
                        }
                    }
                }
            } else {
                if ($gettingConstant && $token !== '=') {
                    $gettingConstant = false;
                    $constName = '';
                }
                if ($gettingNamespace && $token === ';') {
                    //If the token is the semicolon, then we're done with the namespace declaration
                    $gettingNamespace = false;
                }
            }
        }
        return $keys;
    }

}