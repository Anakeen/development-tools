<?php

class phpCSDynacase_Sniffs_General_NoLogicalBooleanOperatorSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(
            T_LOGICAL_AND,
            T_LOGICAL_OR
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
        $constName = $tokens[$stackPtr]['content'];

        $error = '"%s" is a logical boolean operator. You should replace it with a boolean boolean operator (&&, ||) and use parenthesis for the priority issue';
        $data = array(
            $tokens[$stackPtr]['content']
        );
        $phpcsFile->addError($error, $stackPtr, 'NoLogicalBooleanOperator', $data);

    } //end process()


} //end class


?>