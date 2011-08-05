<?php

class phpCSDynacase_Sniffs_General_FunctionInAClassSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(
            T_FUNCTION
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

        $functionIsInClass = $phpcsFile->getCondition($stackPtr, T_CLASS);

        if (!$functionIsInClass) {

            $error = 'You have to to embed your function %s in a class';
            $data = array(
                $phpcsFile->getDeclarationName($stackPtr)
            );
            $phpcsFile->addError($error, $stackPtr, 'FunctionInAClass', $data);
        }

    } //end process()


} //end class


?>