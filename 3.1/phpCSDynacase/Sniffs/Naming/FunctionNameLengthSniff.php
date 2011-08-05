 <?php

class phpCSDynacase_Sniffs_Naming_FunctionNameLengthSniff implements PHP_CodeSniffer_Sniff
{

    public $functionLengthMin = 3;

    public $functionLengthMax = 20;


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

        $functionName = $phpcsFile->getDeclarationName($stackPtr);

        if (!(strlen($functionName) >= $this->functionLengthMin && strlen($functionName) <= $this->functionLengthMax) ) {
            $error = "A function name need to have between 3 and 20 characters. (%s doesn't complied)";
            $data = array(
                $functionName
            );
            $phpcsFile->addError($error, $stackPtr, 'FunctionNameLength', $data);
        }

    } //end process()


}