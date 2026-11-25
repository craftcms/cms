<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http;

use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Http\Middleware\CollectCacheInfo;
use CraftCms\Cms\View\CacheCollectors\DependencyCollector;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Container\Attributes\Scoped;

/**
 * The tags the current response depends on and the earliest expiry date among them.
 *
 * For GET and HEAD site requests outside the control panel and actions, {@see CollectCacheInfo} collects the tags and
 * expiry dates of the elements the request queries for the whole request. Templates, controllers and plugins add their
 * own through the `ResponseCacheInfo` facade, which is also a Twig global:
 *
 * ```twig
 * {% do ResponseCacheInfo.addTags(['nav']) %}
 * ```
 *
 * They're collected like the cache info Craft collects for elements. Added inside a `{% cache %}` block, they're
 * stored with it and added again on a cache hit, and `ElementCaches::invalidateTags()` clears the blocks tagged with
 * them. Other responses have no cache info.
 *
 * Craft doesn't send the tags anywhere itself. Cache integrations, such as `craftcms/http-cache` or a plugin, read
 * them with {@see getTags()} from wherever they run, and can cap their own lifetimes, such as `CDN-Cache-Control`,
 * at {@see getExpiryDate()}.
 *
 * @since 6.0.0
 */
#[Scoped]
class ResponseCacheInfo
{
    private bool $collecting = false;

    public function __construct(
        private readonly ElementCaches $elementCaches,
        private readonly DependencyCollector $dependencies,
    ) {}

    /**
     * @param  string|list<string>  $tags
     */
    public function addTags(string|array $tags): static
    {
        $this->elementCaches->collectCacheTags(
            array_values(array_filter(array_map(strval(...), (array) $tags), fn (string $tag) => $tag !== '')),
        );

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getTags(): array
    {
        return $this->collecting ? $this->dependencies->collectedTags() : [];
    }

    /**
     * Adds an expiry date the response depends on. Past dates are ignored.
     */
    public function addExpiryDate(DateTimeInterface $date): static
    {
        $this->elementCaches->setCacheExpiryDate($date);

        return $this;
    }

    /**
     * The earliest expiry date the response depends on, if any.
     */
    public function getExpiryDate(): ?DateTimeImmutable
    {
        return $this->collecting ? $this->dependencies->collectedExpiryDate() : null;
    }

    /**
     * Starts collecting the response's cache info until {@see stop()}, which {@see CollectCacheInfo} calls when the
     * request terminates.
     *
     * @internal
     */
    public function start(): void
    {
        $this->elementCaches->startCollectingCacheInfo();
        $this->collecting = true;
    }

    /**
     * Stops collecting. The response has no cache info after this.
     *
     * @internal
     */
    public function stop(): void
    {
        if (! $this->collecting) {
            return;
        }

        $this->collecting = false;
        $this->elementCaches->stopCollectingCacheInfo();
    }
}
