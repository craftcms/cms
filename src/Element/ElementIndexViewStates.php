<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use Illuminate\Support\Facades\Cache;

/**
 * The element index view states the browser keeps in local storage, mirrored
 * for the current session so a page load can render the rows for them.
 *
 * The control panel pushes every view state once per session, then each one
 * again as it changes (see `view-state-sync.ts`). They live in the cache
 * rather than in the session payload: Laravel writes the whole session back
 * at the end of every request, so a prefetch that started before a push could
 * otherwise put the old view state back.
 *
 * @since 6.0.0
 */
class ElementIndexViewStates
{
    /**
     * Whether the browser has pushed its view states this session.
     */
    public function synced(): bool
    {
        return Cache::has($this->cacheKey());
    }

    /**
     * Stores view states by their local storage key, such as
     * `elementindex.CraftCms\Cms\Entry\Elements\Entry.index`.
     *
     * @param  array<string, array<string, mixed>>  $states
     * @param  bool  $all  Whether these are all of the browser's view states.
     */
    public function put(array $states, bool $all = false): void
    {
        $ttl = now()->addMinutes((int) config('session.lifetime', 120));

        foreach ($states as $key => $state) {
            Cache::put($this->cacheKey($key), $state, $ttl);
        }

        if ($all) {
            Cache::put($this->cacheKey(), true, $ttl);
        }
    }

    /**
     * An index's view state, or null when the browser has none for it.
     *
     * @param  class-string  $elementType
     * @return array<string, mixed>|null
     */
    public function get(string $elementType, string $context): ?array
    {
        $state = Cache::get($this->cacheKey("elementindex.$elementType.$context"));

        return is_array($state) ? $state : null;
    }

    private function cacheKey(?string $key = null): string
    {
        $prefix = 'elementIndexViewStates:'.session()->getId();

        return $key === null ? $prefix : "$prefix:$key";
    }
}
