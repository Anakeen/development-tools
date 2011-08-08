<?php

/**
 * Check the visibility of the function except if the function is a closure
 *
 * @author charles
 */
class phpCSDynacase_Sniffs_Class_FunctionVisibilitySniff implements PHP_CodeSniffer_Sniff
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

        $functionProperties = $phpcsFile->getMethodProperties($stackPtr);
	// verify for function in a class : level > 0
        if (!$functionProperties['is_closure'] && (!$functionProperties['scope_specified'] && $tokens[$stackPtr]['level']>0)) {

            $error = 'You have to specify a scope to your function %s, or to embed it in a class';
            $data = array(
                $tokens[$stackPtr]['content']
            );
            $phpcsFile->addError($error, $stackPtr, 'FunctionMustHaveVisibility', $data);
        }

    } //end process()


} //end class


?>