<?php

class phpCSDynacase_Sniffs_General_IncludingFileSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(
            T_REQUIRE_ONCE,
            T_INCLUDE_ONCE,
            T_REQUIRE,
            T_INCLUDE
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
        $tokenCode = $tokens[$stackPtr]['code'];

        //test if the input token is authorized
        if ($tokenCode === T_INCLUDE_ONCE || $tokenCode === T_REQUIRE || $tokenCode === T_INCLUDE) {
            $error = 'Include, require and include_once are not allowed. Use "require_once" instead';
            $phpcsFile->addError($error, $stackPtr, 'NoUseOfRequireIncludeOnceInclude');
        }
        //test if a opening parenthesis is the next token
        $nextToken = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, ($stackPtr + 1), null, true);
        if ($tokens[$nextToken]['code'] === T_OPEN_PARENTHESIS) {
            $error = '"%s" is a statement not a function; no parentheses are required';
            $data = array(
                $tokens[$stackPtr]['content']
            );
            $phpcsFile->addError($error, $stackPtr, 'BracketsNotRequired', $data);
        }
    } //end process()


} //end class


?>