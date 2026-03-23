<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Extensions;

use Craft;
use craft\errors\AssetException;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers;
use CraftCms\Cms\View\InputNamespace;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Override;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use yii\base\InvalidConfigException;
use yii\helpers\Markdown;

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
        ];
    }

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
        return Craft::$app->getElements()->parseRefs((string) $str, $siteId);
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

        return app(HtmlSanitizers::class)->sanitize($html, $sanitizer);
    }

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
            if ($flavor !== null && ! in_array($flavor, ['original', 'pre-encoded'])) {
                throw new InvalidArgumentException('The Markdown flavor cannot be specified when passing `encode=true`.');
            }

            $markdown = Html::encode($markdown);
            $flavor = 'pre-encoded';
        }

        if ($inlineOnly) {
            return Markdown::processParagraph((string) $markdown, $flavor);
        }

        return Markdown::process((string) $markdown, $flavor);
    }

    /**
     * @throws InvalidConfigException
     * @throws AssetException
     */
    public function dataUrlFunction(Asset|string $file, ?string $mimeType = null): string
    {
        try {
            if ($file instanceof Asset) {
                return $file->getDataUrl();
            }

            return Html::dataUrl(Aliases::get($file), $mimeType);
        } catch (InvalidArgumentException $e) {
            Log::warning($e->getMessage(), [__METHOD__]);

            return '';
        }
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

    public function tagFunction(string $type, array $attributes = []): string
    {
        $html = Arr::pull($attributes, 'html', '');
        $text = Arr::pull($attributes, 'text');

        if ($text !== null) {
            $html = Html::encode($text);
        }

        return Html::tag($type, $html, $attributes);
    }
}
