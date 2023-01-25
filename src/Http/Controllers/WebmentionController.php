<?php

namespace janboddez\Webmention\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class WebmentionController
{
    public function handle(Request $request)
    {
        abort_unless($request->filled('source'), 400, __('Missing source URL.'));
        abort_unless($request->filled('target'), 400, __('Missing target URL.'));

        abort_unless(
            $source = filter_var($request->input('source'), FILTER_VALIDATE_URL),
            400,
            __('Invalid source URL.')
        );

        abort_unless(
            $target = filter_var($request->input('target'), FILTER_VALIDATE_URL),
            400,
            __('Invalid target URL.')
        );

        // Of course if we ran this as a standalone service, we'd compare
        // against a(nother) URL in the config. Prevent folks from spamming us
        // with completely random URLs.
        abort_unless(
            parse_url($target, PHP_URL_HOST) === parse_url(url('/'), PHP_URL_HOST),
            400,
            __('Invalid target URL.')
        );

        // This would work best for a standalone service.
        $response = Http::head($target);

        /** @todo: Be more permissive? And follow redirects and whatnot. */
        abort_unless($response->ok(), 400, __('Invalid target URL.'));

        // Saving webmentions separate from final comments, to ease asynchronous
        // processing and allow for future, additional comment sources.
        DB::insert(
            'INSERT INTO webmentions (source, target, status, ip, created_at) VALUES (?, ?, ?, ?, NOW())',
            [$source, $target, 'new', filter_var($request->server('HTTP_CF_CONNECTING_IP'), FILTER_VALIDATE_IP) ? $request->server('HTTP_CF_CONNECTING_IP') : $request->ip()]
        );

        return response()->json([], 202);
    }
}
