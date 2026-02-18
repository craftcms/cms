<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Extensions;

use Craft;
use craft\errors\AssetException;
use craft\helpers\HtmlPurifier;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use yii\base\InvalidConfigException;
use yii\helpers\Markdown;

final class HtmlTwigExtension extends AbstractExtension
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
            new TwigFilter('namespace', InputNamespace::namespaceInputs(...), ['is_safe' => ['html']]),
            new TwigFilter('namespaceAttributes', Html::namespaceAttributes(...), ['is_safe' => ['html']]),
            new TwigFilter('ns', InputNamespace::namespaceInputs(...), ['is_safe' => ['html']]),
            new TwigFilter('namespaceInputName', InputNamespace::namespaceInputName(...)),
            new TwigFilter('namespaceId', InputNamespace::namespaceId(...)),
            new TwigFilter('namespaceInputId', InputNamespace::namespaceId(...)),
            new TwigFilter('parseAttr', $this->parseAttrFilter(...)),
            new TwigFilter('parseRefs', $this->parseRefsFilter(...), ['is_safe' => ['html']]),
            new TwigFilter('prepend', $this->prependFilter(...), ['is_safe' => ['html']]),
            new TwigFilter('purify', $this->purifyFilter(...), ['is_safe' => ['html']]),
            new TwigFilter('removeClass', $this->removeClassFilter(...), ['is_safe' => ['html']]),
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

    public function purifyFilter(?string $html, array|string|null $config = null): ?string
    {
        if ($html === null) {
            return null;
        }

        if (is_string($config)) {
            $path = Craft::$app->getPath()->getConfigPath().DIRECTORY_SEPARATOR.'htmlpurifier'.
                DIRECTORY_SEPARATOR.$config.'.json';
            $config = null;
            if (! is_file($path)) {
                Log::info("No HTML Purifier config found at $path.");
            } else {
                try {
                    $config = Json::decode(file_get_contents($path));
                } catch (InvalidArgumentException) {
                    Log::info("Invalid HTML Purifier config at $path.");
                }
            }
        }

        return HtmlPurifier::process($html, $config);
    }

    public function removeClassFilter(string $tag, array|string $class): string
    {
        $class = Arr::wrap($class);

        try {
            return Html::removeTagAttributes($tag, [
                'class' => $class,
            ]);
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
