<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Events;

use CraftCms\Cms\Http\Middleware\StartLazySession;
use Illuminate\Http\Request;

/**
 * {@see StartLazySession} dispatches this event when it saves the session for a request that arrived without one.
 * Saving the session sends a session cookie, which keeps shared caches from storing the response.
 *
 * @since 6.0.0
 */
readonly class LazySessionSaved
{
    public function __construct(
        public Request $request,
        /**
         * The session keys the request added, changed, or removed, such as `_flash` or a plugin’s cart key.
         *
         * @var list<string>
         */
        public array $keys,
        /**
         * Whether the session ID was regenerated, such as when a user signs in.
         */
        public bool $regenerated,
        /**
         * Where the session’s CSRF token was found in the response, if it was. Streamed responses report `body`,
         * because their content can’t be checked.
         *
         * @var 'body'|'header'|'cookie'|null
         */
        public ?string $tokenLocation,
    ) {}
}
