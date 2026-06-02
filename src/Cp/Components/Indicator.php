<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Component\Contracts\Statusable;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Stringable;

use function CraftCms\Cms\t;

/**
 * PHP counterpart to the `<craft-indicator>` web component.
 *
 * Provides a Filament-style fluent builder that mirrors the web component's
 * API (`fill`, `size`, `appearance`, `label`) and renders the matching
 * `<craft-indicator>` markup.
 *
 * @see packages/craftcms-cp/src/components/indicator/indicator.ts
 */
class Indicator implements Htmlable, Stringable
{
    protected string|Color|null $fill = null;

    protected string $size = 'md';

    protected string $appearance = 'solid';

    protected ?string $label = null;

    /** @var array<string, mixed> Additional HTML attributes for the host element. */
    protected array $attributes = [];

    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Builds the control-panel status indicator for a given status.
     *
     * Most statuses render as a colored dot. The special `draft` status renders
     * the draft icon instead. The accessible label is prefixed with "Status:".
     *
     * @param  array{label?: string|null, color?: string|Color|null}  $def  Status definition (see {@see Statusable::statuses()}).
     * @param  array<string, mixed>  $attributes  Additional HTML attributes for the host element.
     */
    public static function forStatus(string $status, array $def = [], array $attributes = []): Htmlable
    {
        $label = array_key_exists('label', $def) ? $def['label'] : ucfirst($status);

        if ($status === 'draft') {
            return new HtmlString(Html::tag('span', '', Arr::merge($attributes, [
                'data' => ['icon' => 'draft'],
                'class' => 'icon',
                'role' => 'img',
                'aria' => [
                    'label' => sprintf('%s %s', t('Status:'), $label ?? t('Draft')),
                ],
            ])));
        }

        return static::make()
            ->fill(($def['color'] ?? null) ?? $status)
            ->label(sprintf('%s %s', t('Status:'), $label))
            ->attributes($attributes);
    }

    /**
     * Sets the indicator color. Accepts a semantic keyword (e.g. `success`,
     * `default`), any CSS color value, or a {@see Color} enum case.
     */
    public function fill(string|Color|null $fill): static
    {
        $this->fill = $fill;

        return $this;
    }

    /**
     * @param  'md'|'lg'  $size
     */
    public function size(string $size): static
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @param  'solid'|'empty'  $appearance
     */
    public function appearance(string $appearance): static
    {
        $this->appearance = $appearance;

        return $this;
    }

    /**
     * Convenience for {@see self::appearance()} — renders the hollow (ring-only) variant.
     */
    public function empty(bool $empty = true): static
    {
        $this->appearance = $empty ? 'empty' : 'solid';

        return $this;
    }

    /**
     * Sets the accessible label, exposed as `aria-label` on the host element.
     */
    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Merges additional HTML attributes (e.g. `slot`, `class`) onto the host element.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function attributes(array $attributes): static
    {
        $this->attributes = Arr::merge($this->attributes, $attributes);

        return $this;
    }

    public function toHtml(): string
    {
        $fill = $this->fill instanceof Color ? $this->fill->value : $this->fill;

        // Defaults are omitted so the rendered markup stays clean; the web
        // component applies the same `md`/`solid` defaults itself.
        return Html::tag('craft-indicator', '', Arr::merge($this->attributes, [
            'fill' => $fill,
            'size' => $this->size === 'md' ? null : $this->size,
            'appearance' => $this->appearance === 'solid' ? null : $this->appearance,
            'label' => $this->label,
        ]));
    }

    public function __toString(): string
    {
        return $this->toHtml();
    }
}
