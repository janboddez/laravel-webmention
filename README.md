# Webmention for Laravel

This package registers a `/webmention` route that accepts incoming webmentions. After validation, `source` and `target` are stored in the `webmentions` table, to be processed asynchronously. (_How_ that happens is up to you; typically, you'd fetch the source URL, parse it for microformats, and somehow map those to an instance of, e.g., a `Comment` model.)

It also lets you send webmentions from anywhere in your Laravel app, like so:

```
use janboddez\Webmention\WebmentionSender;

...

// Somewhere in your app, like after a post is first published.
$result = WebmentionSender::send($source, $target);
```
`$result` is an associated array containing the target, endpoint, result (`true` or `false`), HTTP status code, and timestamp.

(`WebmentionSender::send()` will look for a Webmention endpoint on your behalf, no worries.)

Or, even better, have it automatically discover any links in your post content:
```
$results = [];

foreach (WebmentionSender::findLinks($post->content) as $target) {
  $results[] = WebmentionSender::send($post->permalink, $target);
}
```
