# PHP Capsule Listener

Usage:

- `show_queries()` to show queries, as they execute, from where you call this function (stick in `bootstrap.php` to show them all) 
- `hide_queries()` to hide further queries (handy when only interested in specific queries)

Installation:

`composer --require-dev fusionspim/php-capsule-listener`

Ideas:

- Make public?
- *Optional* `show_queries(LoggerInterface)` which skips `dump()`?
