<?php

/**
 * HTTP Cache Configuration
 *
 * Settings for the caches in front of Craft, such as a CDN or Varnish: how
 * response tags are sent, and the clients that invalidate them.
 *
 * `default` names the client that invalidates the page cache. Each client has
 * a `driver` (`noop`, `varnish`, `fastly`, `cloudflare`, or one registered with
 * `HttpCache::extend()`), optional `servers` and `base_uri` for sending
 * requests, and the FOSHttpCache client's own options:
 *
 * ```php
 * 'default' => 'site',
 * 'clients' => [
 *     'site' => [
 *         'driver' => 'cloudflare',
 *         'authentication_token' => env('CLOUDFLARE_TOKEN'),
 *         'zone_identifier' => env('CLOUDFLARE_ZONE'),
 *     ],
 * ],
 * ```
 *
 * Leave `tags.formatter` null for caches that don't read a tag header. Tags
 * are still collected for drivers that keep their own index. Otherwise, set a
 * FOSHttpCache `TagHeaderFormatter` class, with its constructor arguments in
 * `tags.formatter_options`. For example, Fastly's `Surrogate-Key`:
 *
 * ```php
 * 'tags' => [
 *     'formatter' => \FOS\HttpCache\TagHeaderFormatter\CommaSeparatedTagHeaderFormatter::class,
 *     'formatter_options' => ['headerName' => 'Surrogate-Key', 'glue' => ' '],
 * ],
 * ```
 */

declare(strict_types=1);

return [
    'default' => 'noop',
    'clients' => [
        'noop' => [
            'driver' => 'noop',
        ],
    ],
    'tags' => [
        'formatter' => null,
        'formatter_options' => [],
    ],
];
