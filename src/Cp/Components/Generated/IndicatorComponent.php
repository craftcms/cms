<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components\Generated;

use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use Illuminate\Contracts\Support\Htmlable;
use Stringable;

/**
 * Indicators are used to visually represent the status of an object.
 * Most of the time, you won't want to use the component directly but instead
 * should use one of the status components.
 *
 * @generated from the `craft-indicator` custom element. Do not edit by hand.
 *           Run `npm run generate:php` in packages/craftcms-cp to regenerate.
 *           Add behavior in the concrete subclass, not here.
 */
abstract class IndicatorComponent implements Htmlable, Stringable
{
    protected string $size = 'md';

    protected Color|string $fill = 'var(--c-color-fill-loud)';

    protected ?string $label = null;

    protected string $appearance = 'solid';

    /** @var array<string, mixed> Additional HTML attributes for the host element. */
    protected array $attributes = [];

    final public function __construct() {}

    public static function make(): static
    {
        return new static;
    }

    /**
     * @param  'md' | 'lg'  $size
     */
    public function size(string $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function fill(Color|string $fill): static
    {
        $this->fill = $fill;

        return $this;
    }

    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @param  'solid' | 'empty'  $appearance
     */
    public function appearance(string $appearance): static
    {
        $this->appearance = $appearance;

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
        return Html::tag('craft-indicator', '', Arr::merge($this->attributes, [
            'size' => $this->size === 'md' ? null : $this->size,
            'fill' => $this->fill === 'var(--c-color-fill-loud)' ? null : ($this->fill instanceof Color ? $this->fill->value : $this->fill),
            'label' => $this->label,
            'appearance' => $this->appearance === 'solid' ? null : $this->appearance,
        ]));
    }

    public function __toString(): string
    {
        return $this->toHtml();
    }
}
