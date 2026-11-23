<?php

declare(strict_types=1);

namespace CraftCms\Cms\HttpCache;

use DateTimeImmutable;
use FOS\HttpCache\TagHeaderFormatter\TagHeaderFormatter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Collects the tags the current response depends on.
 *
 * Tags are opaque strings. Craft adds the tags of elements the request queries as they're collected, along with
 * the earliest expiry date among those elements ({@see getExpiryDate()}). Templates, controllers and plugins add
 * their own tags through the `ResponseTagger` facade, which is also a Twig global:
 *
 * ```twig
 * {% do ResponseTagger.addTags(['nav']) %}
 * ```
 *
 * The tags reach the response only when a tag header formatter is configured. Cache drivers read them with
 * {@see getTags()} from wherever they run, and can cap their own lifetimes, such as `CDN-Cache-Control`, at
 * {@see getExpiryDate()}.
 *
 * Unlike FOSHttpCache's `ResponseTagger`, tagging a response doesn't clear the tags, so drivers can still read them.
 *
 * @since 6.0.0
 */
class ResponseTagger
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

    public function __construct(
        private readonly TagEncoder $encoder,
        private readonly ?TagHeaderFormatter $formatter = null,
    ) {}

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
     * Starts recording the tags added from now on, such as inside a `{% cache %}` block. Captures can nest.
     */
    public function startCapture(): void
    {
        $this->captures[] = [];
    }

    /**
     * Stops the innermost capture and returns the tags added since it started.
     *
     * @return list<string>
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
    public function setExpiryTimestamp(int $timestamp): void
    {
        if ($this->expiryTimestamp === null || $timestamp < $this->expiryTimestamp) {
            $this->expiryTimestamp = $timestamp;
        }
    }

    /**
     * The earliest expiry date among the elements the response depends on, if any expire.
     */
    public function getExpiryDate(): ?DateTimeImmutable
    {
        return $this->expiryTimestamp === null ? null : new DateTimeImmutable("@{$this->expiryTimestamp}");
    }

    /**
     * Adds the encoded tags to the response's tag header, if a formatter is configured.
     */
    public function tagResponse(Response $response): void
    {
        if ($this->formatter === null || $this->tags === []) {
            return;
        }

        $response->headers->set(
            $this->formatter->getTagsHeaderName(),
            $this->formatter->getTagsHeaderValue($this->encoder->encode($this->getTags())),
            false,
        );
    }
}
