<?php

namespace janboddez\Webmention;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebmentionSender
{
    public static function send(string $source, string $target): array
    {
        // Find endpoint, if any.
        $endpoint = static::discoverEndpoint($target);

        if (! $endpoint) {
            Log::notice(__('No Webmention endpoint found for :target', [
                'target' => $target,
            ]));

            return [
                'result' => false,
                'target' => $target,
                'endpoint' => null,
                'status' => null,
                'sent' => null,
            ];
        }

        // Send webmention.
        $response = Http::asForm()->post($endpoint, [
            'source' => $source,
            'target' => $target,
        ]);

        return [
            'result' => $response->successful(),
            'target' => $target,
            'endpoint' => $endpoint,
            'status' => $response->status(),
            'sent' => Carbon::now()->toDateTimeString(),
        ];
    }

    public static function discoverEndpoint(string $url): ?string
    {
        /** @todo: Use Guzzle, allow redirects. */
        $response = Http::head($url);

        $links = $response->header('link');
        $links = explode(',', $links);

        if (! empty($links)) {
            foreach ($links as $link) {
                // phpcs:ignore Generic.Files.LineLength.TooLong
                if (! preg_match('/<(.[^>]+)>;\s+rel\s?=\s?[\"\']?(http:\/\/)?webmention(\.org)?\/?[\"\']?/i', $link, $matches)) {
                    continue;
                }

                return static::absolutizeUrl($matches[1], $url);
            }
        }

        $response = Http::get($url);

        if (! $response->successful()) {
            return null;
        }

        $content = $response->body();
        $content = mb_convert_encoding($content, 'HTML-ENTITIES', mb_detect_encoding($content));

        libxml_use_internal_errors(true);

        $doc = new \DOMDocument();
        $doc->loadHTML($content);

        $xpath = new \DOMXPath($doc);

        // phpcs:ignore Generic.Files.LineLength.TooLong
        foreach ($xpath->query('(//link|//a)[contains(concat(" ", @rel, " "), " webmention ") or contains(@rel, "webmention.org")]/@href') as $result) {
            return static::absolutizeUrl($result->value, $url);
        }

        return null;
    }

    public static function findLinks(string $html): array
    {
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', mb_detect_encoding($html));

        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML($html);
        $xpath = new \DOMXPath($doc);

        $urls = [];

        foreach ($xpath->query('//a/@href') as $result) {
            $urls[] = $result->value;
        }

        return $urls;
    }

    public static function absolutizeUrl(string $url, string $baseUrl): ?string
    {
        $absoluteUrl = \Mf2\resolveUrl($baseUrl, $url);

        if (! filter_var($absoluteUrl, FILTER_VALIDATE_URL)) {
            return null;
        }

        return $absoluteUrl;
    }
}
