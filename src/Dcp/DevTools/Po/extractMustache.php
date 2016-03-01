<?php

namespace Dcp\DevTools\Po;

use Mustache_Engine;
use Mustache_Tokenizer;

class extractMustache
{
    protected $tokens;
    protected $file;

    const TRANSLATION_TAG_NAME = 'i18n';
    const TRANSLATION_TAG_OPEN = '[[';
    const TRANSLATION_TAG_CLOSE = ']]';

    public function __construct($file)
    {
        if (!file_exists($file) || !is_readable($file)) {
            throw new \Exception("$file does not exists or is not readable");
        }
        $this->file = $file;
    }

    protected function getTokens() {
        if (null === $this->tokens) {
            $mustacheEngine = new Mustache_Engine();
            $this->tokens = $mustacheEngine->getTokenizer()->scan(
                file_get_contents($this->file),
                sprintf(
                    '%s %s',
                    self::TRANSLATION_TAG_OPEN,
                    self::TRANSLATION_TAG_CLOSE
                )
            );
        }
        return $this->tokens;
    }

    public function extractKeys()
    {
        /*
         * Note : simpler approach like
         *
         * $mustacheEngine->render(
         *      $content,
         *      [
         *          'i18n' => function($string) use (&$keys) {
         *              …
         *          }
         *      ]
         *  )
         *
         *  do not handle markup like [[#foo]][[#i18n]]ctxt::key[[/i18n]][[/foo]]
         */

        $keys = [];

        //Set helper values to know that we have found the i18ns token
        //and need to collect the string values after them
        $gettingKey = false;

        foreach ($this->getTokens() as $token) {
            if (is_array($token)) {

                //If this token is a translation open token,
                //then flag that the next token will be the key
                if ($token[Mustache_Tokenizer::TYPE] === Mustache_Tokenizer::T_SECTION
                    && $token[Mustache_Tokenizer::NAME] === self::TRANSLATION_TAG_NAME
                ) {
                    $gettingKey = true;
                }

                //If this token is a translation close token,
                //then flag that the next token will be the key
                if ($token[Mustache_Tokenizer::TYPE] === Mustache_Tokenizer::T_END_SECTION
                    && $token[Mustache_Tokenizer::NAME] === self::TRANSLATION_TAG_NAME
                ) {
                    $gettingKey = false;
                }

                //While we're grabbing the key...
                if ($gettingKey === true) {

                    //If the token is a text...
                    if ($token[Mustache_Tokenizer::TYPE] === Mustache_Tokenizer::T_TEXT) {
                        //get context and key
                        $matches = array();
                        $pregResult = preg_match(
                            '/^(?:(?<context>.+[^(::)])::)?(?<key>.+)$/',
                            $token[Mustache_Tokenizer::VALUE],
                            $matches
                        );

                        //we've got a PCRE error or the message is not well formed
                        if(!$pregResult) {
                            throw new Exception(
                                sprintf(
                                    'message %s in file %s at line %d is not well formed (error code is %s)',
                                    $token[Mustache_Tokenizer::VALUE],
                                    $this->file,
                                    $token[Mustache_Tokenizer::LINE],
                                    preg_last_error()
                                )
                            );
                        } else {
                            //Append the key to the list
                            $keys[$token[Mustache_Tokenizer::VALUE]] = [
                                'context' => $matches['context'],
                                'key' => $matches['key'],
                                'file' => basename($this->file),
                                'line' => $token[Mustache_Tokenizer::LINE]
                            ];
                        }
                    }
                }
            }
        }
        return $keys;
    }

}