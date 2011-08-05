<?php

class phpCSDynacase_Sniffs_Naming_VariableNameLengthSniff implements PHP_CodeSniffer_Sniff
{

    public $variableLengthMin = 1 ;

    public $variableLengthMax = 20;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(
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
        $variableName = substr($tokens[$stackPtr]['content'], 1);

        if (!(strlen($variableName) >= $this->variableLengthMin && strlen($variableName) <= $this->variableLengthMax)) {
            $error = "A variable name need to have between 3 and 20 characters. (%s doesn't complied)";
            $data = array(
               $variableName
            );
            $phpcsFile->addError($error, $stackPtr, 'VariableNameLength', $data);
        }

    } //end process()


}