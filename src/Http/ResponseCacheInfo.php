<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http;

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

    /**
     * Tags added since each open {@see startCapture()} call, innermost last.
     *
     * @var list<array<string, true>>
     */
    private array $captures = [];

    private ?int $expiryTimestamp = null;

    private bool $collectingElements = false;

    /**
     * @param  string|list<string>  $tags
     */
    public function addTags(string|array $tags): static
    {
        foreach ((array) $tags as $tag) {
            $tag = (string) $tag;

            if ($tag === '') {
                continue;
            }

            $this->tags[$tag] = true;

            foreach (array_keys($this->captures) as $i) {
                $this->captures[$i][$tag] = true;
            }
        }

        return $this;
    }

    /**
     * Sets whether the element tags and expiry dates Craft collects are added to the response. {@see CollectCacheInfo}
     * turns this on for the requests it collects for.
     *
     * @internal
     */
    public function collectElements(bool $collect = true): void
    {
        $this->collectingElements = $collect;
    }

    /**
     * Adds element tags Craft collected, if {@see collectElements()} is on.
     *
     * They aren't captured for `{% cache %}` blocks, which replay element tags themselves.
     *
     * @param  list<string>  $tags
     *
     * @internal
     */
    public function addElementTags(array $tags): void
    {
        if (! $this->collectingElements) {
            return;
        }

        foreach ($tags as $tag) {
            if ($tag !== '') {
                $this->tags[$tag] = true;
            }
        }
    }

    /**
     * Adds an element expiry date Craft collected, if {@see collectElements()} is on.
     *
     * @internal
     */
    public function addElementExpiryDate(DateTimeInterface $date): void
    {
        if ($this->collectingElements) {
            $this->addExpiryDate($date);
        }
    }

    /**
     * Starts recording the tags added with {@see addTags()} from now on, for a `{% cache %}` block to replay on a hit.
     * Captures can nest.
     *
     * @internal
     */
    public function startCapture(): void
    {
        $this->captures[] = [];
    }

    /**
     * Stops the innermost capture and returns the tags added since it started.
     *
     * @return list<string>
     *
     * @internal
     */
    public function stopCapture(): array
    {
        return array_map(strval(...), array_keys(array_pop($this->captures) ?? []));
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
        $timestamp = $date->getTimestamp();

        if ($this->expiryTimestamp === null || $timestamp < $this->expiryTimestamp) {
            $this->expiryTimestamp = $timestamp;
        }

        return $this;
    }

    /**
     * The earliest expiry date the response depends on, if any.
     */
    public function getExpiryDate(): ?DateTimeImmutable
    {
        return $this->expiryTimestamp === null ? null : new DateTimeImmutable("@{$this->expiryTimestamp}");
    }
}
