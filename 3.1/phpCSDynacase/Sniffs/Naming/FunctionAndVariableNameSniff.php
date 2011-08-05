<?php

class phpCSDynacase_Sniffs_Naming_FunctionAndVariableNameSniff implements PHP_CodeSniffer_Sniff
{

    public $nameRegExp = "/^[a-z_][a-zA-Z0-9]*/";


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(
            T_FUNCTION,
            T_VARIABLE
        );

    } //end register()


    /** 
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in the
     * stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $name = "";
        $tokenCode = $tokens[$stackPtr]['code'];

        if ($tokenCode === T_FUNCTION) {
            $name = $phpcsFile->getDeclarationName($stackPtr);
        }else {
            $name = substr($tokens[$stackPtr]['content'], 1);
        }

        if (!(preg_match($this->nameRegExp, $name)) ) {
            $error = "A function or variable name needs to match with this regexp $this->nameRegExp (%s doesn't).";
            $data = array(
                $name
            );
            $phpcsFile->addError($error, $stackPtr, 'FunctionNameLength', $data);
        }
    } //end process()


}