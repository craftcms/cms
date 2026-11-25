<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http;

use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Http\Middleware\CollectCacheInfo;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Container\Attributes\Scoped;

/**
 * Collects the tags the current response depends on and the earliest expiry date among them.
 *
 * Tags are opaque strings. For GET and HEAD site requests outside the control panel and actions, {@see CollectCacheInfo}
 * adds the tags of the elements the request queries, along with their earliest expiry date ({@see getExpiryDate()}).
 * Templates, controllers and plugins add their own tags and expiry dates through the `ResponseCacheInfo` facade,
 * which is also a Twig global:
 *
 * ```twig
 * {% do ResponseCacheInfo.addTags(['nav']) %}
 * ```
 *
 * Tags and expiry dates added inside a `{% cache %}` block are stored with it, so they're added again on a cache hit,
 * and `ElementCaches::invalidateTags()` clears the blocks that depend on a tag.
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
    /**
     * @var array<string, true>
     */
    private array $tags = [];

    private ?int $expiryTimestamp = null;

    private bool $collectingDependencies = false;

    public function __construct(
        private readonly ElementCaches $elementCaches,
    ) {}

    /**
     * @param  string|list<string>  $tags
     */
    public function addTags(string|array $tags): static
    {
        $tags = array_values(array_filter(array_map(strval(...), (array) $tags), fn (string $tag) => $tag !== ''));

        $this->recordTags($tags);

        // So `{% cache %}` blocks and data caches depend on them too.
        $this->elementCaches->collectCacheTags($tags);

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getTags(): array
    {
        return array_map(strval(...), array_keys($this->tags));
    }

    /**
     * Records an expiry date the response depends on, keeping the earliest.
     */
    public function addExpiryDate(DateTimeInterface $date): static
    {
        $this->recordExpiryDate($date);

        // So `{% cache %}` blocks and data caches expire with it too.
        $this->elementCaches->setCacheExpiryDate($date);

        return $this;
    }

    /**
     * The earliest expiry date the response depends on, if any.
     */
    public function getExpiryDate(): ?DateTimeImmutable
    {
        return $this->expiryTimestamp === null ? null : new DateTimeImmutable("@{$this->expiryTimestamp}");
    }

    /**
     * Sets whether the tags and expiry dates Craft collects for caches, such as those of the elements a request
     * queries, are added to the response. {@see CollectCacheInfo} turns this on for the requests it collects for.
     *
     * @internal
     */
    public function collectDependencies(bool $collect = true): void
    {
        $this->collectingDependencies = $collect;
    }

    /**
     * Adds tags Craft collected for caches, if {@see collectDependencies()} is on.
     *
     * @param  list<string>  $tags
     *
     * @internal
     */
    public function addCollectedTags(array $tags): void
    {
        if ($this->collectingDependencies) {
            $this->recordTags($tags);
        }
    }

    /**
     * Adds an expiry date Craft collected for caches, if {@see collectDependencies()} is on.
     *
     * @internal
     */
    public function addCollectedExpiryDate(DateTimeInterface $date): void
    {
        if ($this->collectingDependencies) {
            $this->recordExpiryDate($date);
        }
    }

    /**
     * @param  list<string>  $tags
     */
    private function recordTags(array $tags): void
    {
        foreach ($tags as $tag) {
            if ($tag !== '') {
                $this->tags[$tag] = true;
            }
        }
    }

    private function recordExpiryDate(DateTimeInterface $date): void
    {
        $timestamp = $date->getTimestamp();

        if ($this->expiryTimestamp === null || $timestamp < $this->expiryTimestamp) {
            $this->expiryTimestamp = $timestamp;
        }
    }
}
