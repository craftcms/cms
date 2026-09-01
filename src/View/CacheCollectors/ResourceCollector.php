<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\CacheCollectors;

use CraftCms\Cms\View\Contracts\CacheCollectorInterface;
use CraftCms\Cms\View\Data\ResourceEntry;
use CraftCms\Cms\View\Data\TemplateCacheContext;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Cms\View\HtmlStack;

readonly class ResourceCollector implements CacheCollectorInterface
{
    private const array BUFFER_KEYS = [
        'js',
        'scripts',
        'css',
        'jsFiles',
        'cssFiles',
        'html',
        'metaTags',
        'jsImports',
    ];

    public function __construct(
        private HtmlStack $htmlStack,
    ) {}

    public static function key(): string
    {
        return 'resources';
    }

    public function begin(TemplateCacheContext $context): void
    {
        if (! $context->resources) {
            return;
        }

        $this->htmlStack->startBuffer(self::BUFFER_KEYS);
    }

    /**
     * @return array<string, mixed>
     */
    public function end(TemplateCacheContext $context): array
    {
        if (! $context->resources) {
            return [];
        }

        $buffer = $this->htmlStack->clearBuffer(self::BUFFER_KEYS);

        return array_filter([
            'js' => $buffer['js'],
            'scripts' => array_map($this->resourceArguments(...), $buffer['scripts']),
            'css' => $this->resourceArguments($buffer['css']),
            'jsFiles' => array_map($this->resourceArguments(...), $buffer['jsFiles']),
            'cssFiles' => $this->resourceArguments($buffer['cssFiles']),
            'html' => $buffer['html'],
            'metaTags' => $this->resourceArguments($buffer['metaTags']),
            'jsImports' => $buffer['jsImports'],
        ], fn (mixed $payload) => $payload !== [] && $payload !== null);
    }

    public function apply(mixed $payload, TemplateCacheContext $context): void
    {
        if (! $context->resources || ! is_array($payload)) {
            return;
        }

        foreach (($payload['js'] ?? []) as $pos => $scripts) {
            $pos = Position::tryFrom((int) $pos) ?? Position::BodyEnd;

            foreach ($scripts as $key => $js) {
                if (! is_string($js)) {
                    continue;
                }

                $this->htmlStack->js($js, $pos, $key);
            }
        }

        foreach (($payload['scripts'] ?? []) as $pos => $tags) {
            $pos = Position::tryFrom((int) $pos) ?? Position::BodyEnd;

            foreach ($tags as $key => [$script, $options]) {
                $this->htmlStack->script($script, $pos, $options, $key);
            }
        }

        foreach (($payload['css'] ?? []) as $key => [$css, $options]) {
            $this->htmlStack->css($css, $options, $key);
        }

        foreach (($payload['jsFiles'] ?? []) as $pos => $tags) {
            foreach ($tags as $key => [$url, $options]) {
                $options['position'] = $pos;
                $this->htmlStack->jsFile($url, $options, $key);
            }
        }

        foreach (($payload['cssFiles'] ?? []) as $key => [$url, $options]) {
            $this->htmlStack->cssFile($url, $options, $key);
        }

        foreach (($payload['html'] ?? []) as $pos => $tags) {
            $pos = Position::tryFrom((int) $pos) ?? Position::BodyEnd;

            foreach ($tags as $key => $html) {
                if (! is_string($html)) {
                    continue;
                }

                $this->htmlStack->html($html, $pos, $key);
            }
        }

        foreach (($payload['metaTags'] ?? []) as $key => $options) {
            $this->htmlStack->metaTag($options, $key);
        }

        foreach (($payload['jsImports'] ?? []) as $key => $value) {
            if (! is_string($value)) {
                continue;
            }

            $this->htmlStack->jsImport($key, $value);
        }
    }

    /**
     * @param  array<string, ResourceEntry>  $entries
     * @return array<string, array<array-key, mixed>>
     */
    private function resourceArguments(array $entries): array
    {
        return array_map(fn (ResourceEntry $entry) => $entry->arguments, $entries);
    }
}
