# Webmention for Laravel

This package registers a `/webmention` route that accepts incoming webmentions. After validation, `source` and `target` are stored in the `webmentions` table, to be processed asynchronously.

It also lets you send webmentions from anywhere in your Laravel app, like so:

```
use janboddez\Webmention\WebmentionSender;

...

// Somewhere in your app, like after a post is first published.
if (WebmentionSender::send($source, $target)) {
  // Great success!
}
```
