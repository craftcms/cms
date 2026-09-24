<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Cp\RequestedSite;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Override;

class Slug extends Text
{
    /** @var list<string>|null */
    private ?array $source = null;

    /** @var array<string, string>|null */
    private ?array $charMap = null;

    private bool $charMapWasSet = false;

    private ?string $language = null;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        $source = $control->props['source'] ?? null;

        if ($source !== null && $attributes['name'] !== null) {
            $sourcePath = [...array_slice($control->path, 0, -1), ...$source];
            HtmlStack::jsWithVars(fn ($sourceId, $targetId, $charMap) => <<<JS
new Craft.SlugGenerator('#' + $sourceId, '#' + $targetId, {charMap: $charMap})
JS, [$renderer->inputId($sourcePath), $attributes['id'], $control->props['charMap'] ?? null]);
        }

        return parent::renderHtml($control, $value, $attributes, $renderer);
    }

    public function component(): string
    {
        return 'craft:slug';
    }

    /**
     * Sets the source path relative to this Control's parent path.
     *
     * @param  string|list<string>|null  $source
     */
    public function source(string|array|null $source): static
    {
        if ($source === null) {
            $this->source = null;

            return $this;
        }

        $source = is_string($source) ? explode('.', $source) : array_values($source);

        if ($source === [] || array_any($source, fn (mixed $segment): bool => ! is_string($segment) || $segment === '')) {
            throw new InvalidArgumentException('Slug source paths must contain non-empty string segments.');
        }

        $this->source = $source;

        return $this;
    }

    /** @param array<string, string>|null $charMap */
    public function charMap(?array $charMap): static
    {
        $this->charMap = $charMap;
        $this->charMapWasSet = true;

        return $this;
    }

    public function language(?string $language): static
    {
        $this->language = $language;

        return $this;
    }

    #[Override]
    public function props(mixed $value = null): array
    {
        $charMap = $this->charMap;

        if (! $this->charMapWasSet && $this->source !== null) {
            $language = $this->language
                ?? app(RequestedSite::class)->get()->language;
            $charMap = Str::asciiCharMap(true, $language);
        }

        return Arr::whereNotNull([
            ...parent::props($value),
            'source' => $this->source,
            'charMap' => $charMap,
        ]);
    }
}
