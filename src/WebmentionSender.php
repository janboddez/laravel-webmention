<?php

namespace janboddez\Webmention;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class WebmentionSender
{
    public static function send(string $source, string $target): ?array
    {
        // Find endpoint, if any.
        $endpoint = static::discoverEndpoint($target);
        if (! $endpoint) {
            Log::debug(__('[Webmention] No Webmention endpoint found for :target', [
                'target' => $target,
            ]));

            return null;
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
        $client = new Client([
            'allow_redirects' => true,
        ]);

        /** @todo: Set a proper user agent. */
        $response = $client->request('HEAD', $url);

        $links = $response->getHeader('Link');
        // $links = explode(',', $links);

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

        $crawler = new Crawler((string) $response->getBody());

        // phpcs:ignore Generic.Files.LineLength.TooLong
        $nodes = $crawler->filterXPath('(//link|//a)[contains(concat(" ", @rel, " "), " webmention ") or contains(@rel, "webmention.org")]');
        if ($nodes->count() === 0) {
            return null;
        }

        $endpoint = $nodes->attr('href', null); // Return the `href` value of the first such element.
        if ($endpoint) {
            return static::absolutizeUrl($endpoint, $url);
        }

        return null;
    }

    public static function findLinks(string $html): array
    {
        $crawler = new Crawler($html);
        $urls = $crawler->filterXPath('//a[@href]')->extract(['href']);

        if (! empty($urls)) {
            return $urls;
        }

        return [];
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
