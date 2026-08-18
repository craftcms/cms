<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Extensions;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Exceptions\AssetException;
use CraftCms\Cms\Field\Data\MarkdownData;
use CraftCms\Cms\Markdown\Markdown as MarkdownService;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\HtmlSanitizers;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\View\InputNamespace;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Override;
use RuntimeException;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class HtmlTwigExtension extends AbstractExtension
{
    #[Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('append', $this->appendFilter(...), ['is_safe' => ['html']]),
            new TwigFilter('attr', $this->attrFilter(...), ['is_safe' => ['html']]),
            new TwigFilter('explodeClass', Html::explodeClass(...)),
            new TwigFilter('explodeStyle', Html::explodeStyle(...)),
            new TwigFilter('id', Html::id(...)),
            new TwigFilter('markdown', $this->markdownFilter(...), ['is_safe' => ['html']]),
            new TwigFilter('md', $this->markdownFilter(...), ['is_safe' => ['html']]),
            new TwigFilter('namespace', app(InputNamespace::class)->namespaceInputs(...), ['is_safe' => ['html']]),
            new TwigFilter('namespaceAttributes', Html::namespaceAttributes(...), ['is_safe' => ['html']]),
            new TwigFilter('ns', app(InputNamespace::class)->namespaceInputs(...), ['is_safe' => ['html']]),
            new TwigFilter('namespaceInputName', app(InputNamespace::class)->namespaceInputName(...)),
            new TwigFilter('namespaceId', app(InputNamespace::class)->namespaceId(...)),
            new TwigFilter('namespaceInputId', app(InputNamespace::class)->namespaceId(...)),
            new TwigFilter('parseAttr', $this->parseAttrFilter(...)),
            new TwigFilter('parseRefs', $this->parseRefsFilter(...), ['is_safe' => ['html']]),
            new TwigFilter('prepend', $this->prependFilter(...), ['is_safe' => ['html']]),
            new TwigFilter('removeClass', $this->removeClassFilter(...), ['is_safe' => ['html']]),
            new TwigFilter('sanitize', $this->sanitizeFilter(...), ['is_safe' => ['html']]),
        ];
    }

    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('actionInput', Html::actionInput(...), ['is_safe' => ['html']]),
            new TwigFunction('attr', Html::renderTagAttributes(...), ['is_safe' => ['html']]),
            new TwigFunction('csrfInput', Html::csrfInput(...), ['is_safe' => ['html']]),
            new TwigFunction('dataUrl', $this->dataUrlFunction(...)),
            new TwigFunction('failMessageInput', Html::failMessageInput(...), ['is_safe' => ['html']]),
            new TwigFunction('hiddenInput', Html::hiddenInput(...), ['is_safe' => ['html']]),
            new TwigFunction('input', Html::input(...), ['is_safe' => ['html']]),
            new TwigFunction('ol', Html::ol(...), ['is_safe' => ['html']]),
            new TwigFunction('redirectInput', Html::redirectInput(...), ['is_safe' => ['html']]),
            new TwigFunction('successMessageInput', Html::successMessageInput(...), ['is_safe' => ['html']]),
            new TwigFunction('svg', $this->svgFunction(...), ['is_safe' => ['html']]),
            new TwigFunction('tag', $this->tagFunction(...), ['is_safe' => ['html']]),
            new TwigFunction('ul', Html::ul(...), ['is_safe' => ['html']]),
            new TwigFunction('h', $this->headingFunction(...), ['is_safe' => ['html']]),
            new TwigFunction('h1', fn (array|string $attributes = '') => $this->headingFunction(1, $attributes), ['is_safe' => ['html']]),
            new TwigFunction('h2', fn (array|string $attributes = '') => $this->headingFunction(2, $attributes), ['is_safe' => ['html']]),
            new TwigFunction('h3', fn (array|string $attributes = '') => $this->headingFunction(3, $attributes), ['is_safe' => ['html']]),
            new TwigFunction('h4', fn (array|string $attributes = '') => $this->headingFunction(4, $attributes), ['is_safe' => ['html']]),
            new TwigFunction('h5', fn (array|string $attributes = '') => $this->headingFunction(5, $attributes), ['is_safe' => ['html']]),
            new TwigFunction('h6', fn (array|string $attributes = '') => $this->headingFunction(6, $attributes), ['is_safe' => ['html']]),
            new TwigFunction('heading', $this->headingFunction(...), ['is_safe' => ['html']]),
        ];
    }

    /** @return array<string, mixed> */
    public function parseAttrFilter(string $tag): array
    {
        try {
            return Html::parseTagAttributes($tag, 0, $start, $end, true);
        } catch (InvalidArgumentException $e) {
            Log::warning($e->getMessage(), [__METHOD__]);

            return [];
        }
    }

    public function parseRefsFilter(mixed $str, ?int $siteId = null): string
    {
        return Elements::parseRefs((string) $str, $siteId);
    }

    public function prependFilter(string $tag, string $html, ?string $ifExists = null): string
    {
        try {
            return Html::prependToTag($tag, $html, $ifExists);
        } catch (InvalidArgumentException $e) {
            Log::warning($e->getMessage(), [__METHOD__]);

            return $tag;
        }
    }

    public function sanitizeFilter(?string $html, HtmlSanitizerInterface|string|null $sanitizer = null): ?string
    {
        if ($html === null) {
            return null;
        }

        return HtmlSanitizers::sanitize($html, $sanitizer);
    }

    /** @param list<string>|string $class */
    public function removeClassFilter(string $tag, array|string $class): string
    {
        try {
            $oldClasses = Html::parseTagAttributes($tag)['class'] ?? [];
            $newClasses = array_filter($oldClasses, fn (string $oldClass) => is_string($class) ? $oldClass !== $class : ! in_array($oldClass, $class, true));
            $newTag = Html::modifyTagAttributes($tag, ['class' => false]);

            if (! empty($newClasses)) {
                return Html::modifyTagAttributes($newTag, ['class' => $newClasses]);
            }

            return $newTag;
        } catch (InvalidArgumentException $e) {
            Log::warning($e->getMessage(), [__METHOD__]);

            return $tag;
        }
    }

    public function appendFilter(string $tag, string $html, ?string $ifExists = null): string
    {
        try {
            return Html::appendToTag($tag, $html, $ifExists);
        } catch (InvalidArgumentException $e) {
            Log::warning($e->getMessage(), [__METHOD__]);

            return $tag;
        }
    }

    /** @param array<string, mixed> $attributes */
    public function attrFilter(string $tag, array $attributes): string
    {
        try {
            return Html::modifyTagAttributes($tag, $attributes);
        } catch (InvalidArgumentException $e) {
            Log::warning($e->getMessage(), [__METHOD__]);

            return $tag;
        }
    }

    public function markdownFilter(
        mixed $markdown,
        ?string $flavor = null,
        bool $inlineOnly = false,
        bool $encode = false,
    ): string {
        if ($encode) {
            if ($flavor !== null && ! in_array($flavor, [MarkdownService::FLAVOR_ORIGINAL, MarkdownService::FLAVOR_PRE_ENCODED], true)) {
                throw new InvalidArgumentException('The Markdown flavor cannot be specified when passing `encode=true`.');
            }

            $flavor = MarkdownService::FLAVOR_PRE_ENCODED;
        }

        return new MarkdownData(
            $this->dedent((string) $markdown),
            $flavor ?? MarkdownService::FLAVOR_ORIGINAL,
            encode: $encode,
            inlineOnly: $inlineOnly,
        )->getHtml();
    }

    /**
     * Strips the leading indentation common to every non-blank line.
     *
     * `{% apply md %}` captures its body with the template's own indentation,
     * and CommonMark treats any line indented four or more spaces as a code
     * block — so an indented block would render as `<pre><code>` instead of
     * parsed Markdown. Removing only the shared minimum indentation preserves
     * relative structure (genuine nested code blocks survive) and is a no-op
     * for content that already starts at column 0.
     */
    private function dedent(string $markdown): string
    {
        preg_match_all('/^[ \t]*(?=\S)/m', $markdown, $matches);

        if ($matches[0] === []) {
            return $markdown;
        }

        $min = min(array_map(strlen(...), $matches[0]));

        if ($min === 0) {
            return $markdown;
        }

        return preg_replace(sprintf('/^[ \t]{1,%d}/m', $min), '', $markdown);
    }

    /**
     * @throws RuntimeException
     * @throws AssetException
     */
    public function dataUrlFunction(Asset|string $file, ?string $mimeType = null): string
    {
        try {
            if ($file instanceof Asset) {
                return $file->getDataUrl();
            }

            return Html::dataUrl($file, $mimeType);
        } catch (InvalidArgumentException $e) {
            Log::warning($e->getMessage(), [__METHOD__]);

            return '';
        }
    }

    /** @param array<string, mixed>|string $attributes */
    public function headingFunction(int $level, array|string $attributes = ''): string
    {
        if ($level < 1 || $level > 6) {
            throw new InvalidArgumentException("Invalid heading level: $level");
        }

        return $this->tagFunction("h$level", $attributes);
    }

    public function svgFunction(Asset|string $svg, ?bool $sanitize = null, ?bool $namespace = null, ?string $class = null): string
    {
        $svg = Html::svg($svg, $sanitize, $namespace);

        if ($class !== null) {
            Deprecator::log('svg()-class', 'The `class` argument of the `svg()` Twig function has been deprecated. The `|attr` filter should be used instead.');
            try {
                $svg = Html::modifyTagAttributes($svg, [
                    'class' => $class,
                ]);
            } catch (InvalidArgumentException $e) {
                Log::warning('Unable to add a class to the SVG: '.$e->getMessage(), [__METHOD__]);
            }
        }

        return $svg;
    }

    /** @param array<string, mixed>|string $attributes */
    public function tagFunction(string $type, array|string $attributes = ''): string
    {
        if (is_array($attributes)) {
            $html = Arr::pull($attributes, 'html');
            $text = Arr::pull($attributes, 'text');
        } else {
            $text = $attributes;
            $attributes = [];
        }

        $html ??= Html::encode($text ?? '');

        return Html::tag($type, $html, $attributes);
    }
}
