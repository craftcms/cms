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
 * Tags and expiry dates are cache dependencies, like the ones Craft collects for elements. Added inside a
 * `{% cache %}` block, they're stored with it and added again on a cache hit, and `ElementCaches::invalidateTags()`
 * clears the blocks that depend on a tag. Other responses have no cache info.
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

    /**
     * What {@see stop()} collected, for code that runs after it.
     *
     * @var array{tags: list<string>, expiryDate: DateTimeImmutable|null}|null
     */
    private ?array $collected = null;

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
        return $this->collected['tags'] ?? ($this->collecting ? $this->dependencies->collectedTags() : []);
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
        if ($this->collected !== null) {
            return $this->collected['expiryDate'];
        }

        return $this->collecting ? $this->dependencies->collectedExpiryDate() : null;
    }

    /**
     * Starts collecting the response's cache info for the whole request.
     *
     * @internal
     */
    public function start(): void
    {
        $this->elementCaches->startCollectingCacheInfo();
        $this->collecting = true;
    }

    /**
     * Stops collecting, keeping what was collected.
     *
     * @internal
     */
    public function stop(): void
    {
        $this->collected = [
            'tags' => $this->dependencies->collectedTags(),
            'expiryDate' => $this->dependencies->collectedExpiryDate(),
        ];
        $this->collecting = false;
        $this->elementCaches->stopCollectingCacheInfo();
    }
}
