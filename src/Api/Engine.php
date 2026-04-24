<?php

namespace Sunnysideup\FulltextSearchEngineSimple\Api;

use SilverStripe\Assets\File;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\DB;

class Engine
{
    public static function get_matches(string $keywords, ?array $classesToSearch = [SiteTree::class, File::class], ?int $start = 0, ?int $pageLength = 1000)
    {
        $booleanSearchAtAtll =
            str_contains($keywords, ' and ') ||
            str_contains($keywords, ' not ') ||
            str_contains($keywords, '-') ||
            str_contains($keywords, '"') ||
            str_contains($keywords, '+') ||
            str_contains($keywords, '-') ||
            str_contains($keywords, '*');
        if ($booleanSearchAtAtll) {
            $andProcessor = (fn($matches) => ' +' . $matches[2] . ' +' . $matches[4] . ' ');
            $notProcessor = (fn($matches) => ' -' . $matches[3]);

            $keywords = preg_replace_callback('#()("[^()"]+")( and )("[^"()]+")()#i', $andProcessor, $keywords);
            $keywords = preg_replace_callback('#(^| )([^() ]+)( and )([^ ()]+)( |$)#i', $andProcessor, (string) $keywords);
            $keywords = preg_replace_callback('#(^| )(not )("[^"()]+")#i', $notProcessor, (string) $keywords);
            $keywords = preg_replace_callback('#(^| )(not )([^() ]+)( |$)#i', $notProcessor, (string) $keywords);
            $booleanSearch =
                str_contains((string) $keywords, '"') ||
                str_contains((string) $keywords, '+') ||
                str_contains((string) $keywords, '-') ||
                str_contains((string) $keywords, '*');
        } else {
            $booleanSearch = false;
        }

        $keywords = self::add_stars_to_keywords($keywords);

        $results = DB::get_conn()->searchEngine($classesToSearch, $keywords, $start, $pageLength, '"Relevance" DESC', '', $booleanSearch);

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

    protected static function add_stars_to_keywords(string $keywords)
    {
        if (trim($keywords) === '' || trim($keywords) === '0') {
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
                    if (str_ends_with($subword, '"')) {
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
