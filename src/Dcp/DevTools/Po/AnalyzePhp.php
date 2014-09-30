<?php

namespace Dcp\DevTools\Po;

use Dcp\DevTools\Template\Template;

class AnalyzePhp extends Analyze
{

    protected $tokens = array();

    protected function extractToken($inputFile)
    {
        $this->tokens = token_get_all(file_get_contents($inputFile));
    }

    protected function extractExtraLabel()
    {
        // extract searchLabel comment
        $filteredLabel = preg_filter("/.*@(searchLabel)\\s+([^\\n]+)\\n.*/s", "\\2", array_map(function ($t) {
                return $t[1];
            }
            , array_filter($this->tokens, function ($t) {
                return ($t[0] === T_DOC_COMMENT);
            })));

        return $filteredLabel;
    }

    protected function extractStateComment()
    {
        $filteredLabel = array();
        $shortComment = preg_grep("/.*@stateLabel.*/", array_map(function ($t) {
                return $t[1];
            }
            , array_filter($this->tokens, function ($t) {
                return ($t[0] === T_COMMENT);
            })));
        foreach ($shortComment as $labelComment) {
            if (preg_match_all('/_\("(?<key>[^"]+)"\)/', $labelComment, $matches)) {
                foreach ($matches["key"] as $m) {
                    $filteredLabel[] = $m;
                }
            }
        }
        return $filteredLabel;
    }

    protected function extractTransitionComment()
    {
        $filteredLabel = array();
        $shortComment = preg_grep("/.*@transitionLabel.*/", array_map(function ($t) {
                return $t[1];
            }
            , array_filter($this->tokens, function ($t) {
                return ($t[0] === T_COMMENT);
            })));
        foreach ($shortComment as $labelComment) {
            if (preg_match_all('/_\("(?<key>[^"]+)"\)/', $labelComment, $matches)) {
                foreach ($matches["key"] as $m) {
                    $filteredLabel[] = $m;
                }
            }
        }
        return $filteredLabel;
    }

    protected function extractActivityComment()
    {
        $filteredLabel = array();
        $shortComment = preg_grep("/.*@activityLabel.*/", array_map(function ($t) {
                return $t[1];
            }
            , array_filter($this->tokens, function ($t) {
                return ($t[0] === T_COMMENT);
            })));
        foreach ($shortComment as $labelComment) {
            if (preg_match_all('/_\("(?<key>[^"]+)"\)/', $labelComment, $matches)) {
                foreach ($matches["key"] as $m) {
                    $filteredLabel[] = $m;
                }
            }
        }
        return $filteredLabel;
    }

    protected function extractSharpComment()
    {
        $filteredLabel = array();
        // extract sharp comment
        $filteredSharp = array_filter($this->tokens, function ($t) {
            return ($t[0] === T_COMMENT && $t[1][0] === '#');
        });
        foreach ($filteredSharp as $sharpComment) {
            $sharpComment[1][0] = ' ';
            if (preg_match_all('/\sN?_\("([^\)]+)"\)/', $sharpComment[1], $matches)) {
                foreach ($matches[1] as $m) {
                    $filteredLabel[] = $m;
                }
            }
        }
        if (!empty($filteredLabel)) {
            error_log("# comment are deprecated, you should remove it");
        }
        return $filteredLabel;
    }

    public function extract($filesPath)
    {
        $searchLabel = array();
        $sharpComment = array();
        $stateLabel = array();
        $transitionLabel = array();
        $activityLabel = array();
        //Remove blank elements
        $filesPath = array_filter($filesPath, function ($value) {
            return trim($value);
        });
        foreach ($filesPath as $phpInputFile) {
            $this->extractToken($phpInputFile);
            $searchLabel = array_merge($searchLabel, $this->extractExtraLabel());
            $sharpComment = array_merge($sharpComment, $this->extractSharpComment());
            $stateLabel = array_merge($stateLabel, $this->extractStateComment());
            $transitionLabel = array_merge($transitionLabel, $this->extractTransitionComment());
            $activityLabel = array_merge($activityLabel, $this->extractActivityComment());
        }

        $temporaryFile = tempnam(sys_get_temp_dir(), "additionnal_keys_");

        $template = new Template();
        $template->main_render("temporary_php_file",
            array(
                "oldCommentStypeLabel" => $sharpComment,
                "searchLabel" => $searchLabel,
                "stateLabel" => $stateLabel,
                "transitionLabel" => $transitionLabel,
                "activityLabel" => $activityLabel
            ),
            $temporaryFile, true);
        $filesPath[] = $temporaryFile;
        parent::extract($filesPath);

        unset($temporaryFile);
    }
} 