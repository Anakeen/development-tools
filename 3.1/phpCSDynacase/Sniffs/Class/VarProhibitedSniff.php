<?php

class phpCSDynacase_Sniffs_Class_VarProhibitedSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(
            T_VAR
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

        $error = 'You have to set a visibility to your class member';
        $phpcsFile->addError($error, $stackPtr, 'VarProhibited');
    } //end process()


}