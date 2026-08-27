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
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Parser\MarkdownParser;
use League\CommonMark\Renderer\HtmlRenderer;

#[Singleton]
class Markdown
{
    public const string FLAVOR_ORIGINAL = 'original';

    public const string FLAVOR_PRE_ENCODED = 'pre-encoded';

    public const string FLAVOR_GFM = 'gfm';

    public const string FLAVOR_GFM_COMMENT = 'gfm-comment';

    public const string FLAVOR_EXTRA = 'extra';

    /** @var array<string, Closure> */
    private array $flavors = [];

    /** @var array<string, MarkdownConverter> */
    private array $resolvedConverters = [];

    public function __construct()
    {
        $this->extend(self::FLAVOR_ORIGINAL, new CommonMarkFlavor);
        $this->extend(self::FLAVOR_PRE_ENCODED, new CommonMarkFlavor(preEncoded: true));
        $this->extend(self::FLAVOR_GFM, new GfmFlavor);
        $this->extend(self::FLAVOR_GFM_COMMENT, new GfmFlavor("<br>\n"));
        $this->extend(self::FLAVOR_EXTRA, new ExtraFlavor);
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

    /** @return list<string> */
    public function flavors(): array
    {
        return array_keys($this->flavors);
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
        return rtrim($this->convert($markdown, new MarkdownOptions(
            flavor: $flavor,
            inlineOnly: true,
            allowUnsafeLinks: $allowUnsafeLinks,
        )), "\n");
    }

    /** @param callable(Document): void $transform */
    public function transform(string $markdown, callable $transform, ?string $flavor = null): string
    {
        if (ltrim($markdown) === '') {
            return '';
        }

        $options = new MarkdownOptions(flavor: $flavor);
        $environment = $this->converter($options)->getEnvironment();
        $document = new MarkdownParser($environment)->parse($markdown);

        $transform($document);

        return new HtmlRenderer($environment)->renderDocument($document)->getContent();
    }

    public function convert(string $markdown, MarkdownOptions $options): string
    {
        if (ltrim($markdown) === '') {
            return '';
        }

        return $this->converter($options)
            ->convert($markdown)
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
