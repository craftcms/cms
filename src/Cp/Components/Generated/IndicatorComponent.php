<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components\Generated;

use CraftCms\Cms\Cp\Components\ViewComponent;
use CraftCms\Cms\Shared\Enums\Color;

/**
 * Indicators are used to visually represent the status of an object.
 * Most of the time, you won't want to use the component directly but instead
 * should use one of the status components.
 *
 * @generated from the `craft-indicator` custom element. Do not edit by hand.
 *           Run `npm run generate:php` in packages/craftcms-cp to regenerate.
 *           Add behavior in the concrete subclass, not here.
 */
abstract class IndicatorComponent extends ViewComponent
{
    protected string $size = 'md';

    protected Color|string $fill = 'var(--c-color-fill-loud)';

    protected ?string $label = null;

    protected string $appearance = 'solid';

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

    protected function tagName(): string
    {
        return 'craft-indicator';
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            'size' => $this->size === 'md' ? null : $this->size,
            'fill' => $this->fill === 'var(--c-color-fill-loud)' ? null : ($this->fill instanceof Color ? $this->fill->value : $this->fill),
            'label' => $this->label,
            'appearance' => $this->appearance === 'solid' ? null : $this->appearance,
        ];
    }
}
