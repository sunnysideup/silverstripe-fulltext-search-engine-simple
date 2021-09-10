# tl;dr

How to get a list of 1000 results...
```php
$list = Sunnysideup\FullTextSearchEngineSimple\Api\Engine::get_matches(
    'Keywords go here',
    [SiteTree::class, File::class],
    0,
    1000
);
```
