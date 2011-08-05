<?php

class phpCSDynacase_Sniffs_Naming_ClassNameSniff implements PHP_CodeSniffer_Sniff
{

    public $nameRegExp = "/^[A-Z][a-zA-Z0-9]+/";

    public function register()
    {
        return array(
                T_CLASS,
                T_INTERFACE,
               );

    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens    = $phpcsFile->getTokens();

        $name = $phpcsFile->getDeclarationName($stackPtr);

    if (!(preg_match($this->nameRegExp, $name)) ) {
            $error = "A Class or Interface name needs to match with this regexp $this->nameRegExp (%s doesn't).";
            $data = array(
                $name
            );
            $phpcsFile->addError($error, $stackPtr, 'FunctionNameLength', $data);
        }
    }

}

?>