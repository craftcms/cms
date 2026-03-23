<?php

declare(strict_types=1);

namespace CraftCms\Cms\Markdown;

use Closure;
use CraftCms\Cms\Markdown\Flavors\CommonMarkFlavor;
use CraftCms\Cms\Markdown\Flavors\ExtraFlavor;
use CraftCms\Cms\Markdown\Flavors\GfmFlavor;
use Illuminate\Container\Attributes\Singleton;
use InvalidArgumentException;
use League\CommonMark\MarkdownConverter;

#[Singleton]
class Markdown
{
    /** @var array<string, Closure> */
    private array $flavors = [];

    /** @var array<string, MarkdownConverter> */
    private array $resolvedConverters = [];

    public function __construct()
    {
        $this->extend('original', new CommonMarkFlavor);
        $this->extend('pre-encoded', new CommonMarkFlavor(preEncoded: true));
        $this->extend('gfm', new GfmFlavor);
        $this->extend('gfm-comment', new GfmFlavor("<br>\n"));
        $this->extend('extra', new ExtraFlavor);
    }

    public function extend(string $name, callable $flavor): void
    {
        $this->flavors[$name] = $flavor instanceof Closure ? $flavor : Closure::fromCallable($flavor);

        foreach (array_keys($this->resolvedConverters) as $cacheKey) {
            if (str_starts_with($cacheKey, "$name:")) {
                unset($this->resolvedConverters[$cacheKey]);
            }
        }
    }

    public function has(string $name): bool
    {
        return isset($this->flavors[$name]);
    }

    public function parse(string $markdown, ?string $flavor = null, bool $allowUnsafeLinks = false): string
    {
        return $this->convert($markdown, new MarkdownOptions(
            flavor: $flavor,
            allowUnsafeLinks: $allowUnsafeLinks,
        ));
    }

    public function parseParagraph(string $markdown, ?string $flavor = null, bool $allowUnsafeLinks = false): string
    {
        return $this->convert($markdown, new MarkdownOptions(
            flavor: $flavor,
            inlineOnly: true,
            allowUnsafeLinks: $allowUnsafeLinks,
        ));
    }

    public function convert(string $markdown, MarkdownOptions $options): string
    {
        if (ltrim($markdown) === '') {
            return '';
        }

        return $this->converter($options)
            ->convert(str_replace(["\r\n", "\n\r", "\r"], "\n", $markdown))
            ->getContent();
    }

    private function converter(MarkdownOptions $options): MarkdownConverter
    {
        $cacheKey = $options->cacheKey();

        if (isset($this->resolvedConverters[$cacheKey])) {
            return $this->resolvedConverters[$cacheKey];
        }

        $flavor = $options->resolvedFlavor();

        if (! isset($this->flavors[$flavor])) {
            throw new InvalidArgumentException("Unknown Markdown flavor [$flavor].");
        }

        return $this->resolvedConverters[$cacheKey] = value($this->flavors[$flavor], $options);
    }
}
