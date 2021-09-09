<?php

namespace Sunnysideup\FulltextSearchEngineSimple\Api;

class Engine
{
    public $classesToSearch;

    public function getMatches(string $keywords, ?array $classesToSearch = [SiteTree::class, File::class], ?int $pageLenth = 1000)
    {
        $andProcessor = function ($matches) {
            return ' +' . $matches[2] . ' +' . $matches[4] . ' ';
        };
        $notProcessor = function ($matches) {
            return ' -' . $matches[3];
        };

        $keywords = preg_replace_callback('#()("[^()"]+")( and )("[^"()]+")()#i', $andProcessor, $keywords);
        $keywords = preg_replace_callback('#(^| )([^() ]+)( and )([^ ()]+)( |$)#i', $andProcessor, $keywords);
        $keywords = preg_replace_callback('#(^| )(not )("[^"()]+")#i', $notProcessor, $keywords);
        $keywords = preg_replace_callback('#(^| )(not )([^() ]+)( |$)#i', $notProcessor, $keywords);

        $keywords = $this->addStarsToKeywords($keywords);

        $booleanSearch =
            false !== strpos($keywords, '"') ||
            false !== strpos($keywords, '+') ||
            false !== strpos($keywords, '-') ||
            false !== strpos($keywords, '*');
        $results = DB::get_conn()->searchEngine($this->classesToSearch, $keywords, $start, $pageLength, '"Relevance" DESC', '', $booleanSearch);

        // filter by permission
        if ($results) {
            foreach ($results as $result) {
                if (! $result->canView()) {
                    $results->remove($result);
                }
            }
        }

        return $results;
    }

    protected function addStarsToKeywords(string $keywords)
    {
        if (! trim($keywords)) {
            return '';
        }
        // Add * to each keyword
        $splitWords = preg_split('# +#', trim($keywords));
        $newWords = [];
        foreach ($splitWords as $i => $splitWord) {
            $word = $splitWord;
            if ('"' === $word[0]) {
                while (++$i < count($splitWords)) {
                    $subword = $splitWord;
                    $word .= ' ' . $subword;
                    if ('"' === substr($subword, -1)) {
                        break;
                    }
                }
            } else {
                $word .= '*';
            }
            $newWords[] = $word;
        }

        return implode(' ', $newWords);
    }
}
