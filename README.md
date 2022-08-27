# Webmention for Laravel

This package registers a `/webmention` route that accepts incoming webmentions. After validation, `source` and `target` are stored in the `webmentions` table, to be processed asynchronously.

It also lets you send webmentions from anywhere in your Laravel app, like so:

```
use janboddez\Webmention\WebmentionSender;

...

// Somewhere in your app, like after a post is first published.
$result = WebmentionSender::send($source, $target);
```
`$result` is an associated array containing the target, endpoint, result (`true` or `false`), HTTP status code, and timestamp.

Or, even better, have it automatically discover any links in your post content:
```
$results = [];

foreach (WebmentionSender::findLinks($post->content) as $target) {
  $results[] = WebmentionSender::send($post->permalink, $target);
}
```
