<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Controls;

use CraftCms\Cms\Cp\Components\InputColor;
use CraftCms\Cms\Cp\Components\SelectColor;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Ui\Contracts\ReadableValueControl;
use CraftCms\Cms\Ui\Contracts\WritableValueControl;
use CraftCms\Cms\Ui\ControlPayload;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Validation\Rules\ColorRule;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class Color extends Control implements ReadableValueControl, WritableValueControl
{
    /** @var list<string> */
    private array $presets = [];

    /** @var array<string, string> */
    private array $presetLabels = [];

    private bool $alpha = false;

    private bool $custom = true;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, UiHtmlRenderer $renderer): string
    {
        $value = $value === null || $value === '' ? null : (string) $value;

        if (($control->props['custom'] ?? true) === false) {
            $presets = $control->props['presets'] ?? [];
            $value = $value !== null ? self::matchingPreset($value, $presets) : null;

            return SelectColor::make()
                ->id($attributes['id'])
                ->name($attributes['name'])
                ->value($value ?? '__blank__')
                ->colors($value === null || in_array($value, $presets, true) ? $presets : [...$presets, $value])
                ->labels($control->props['presetLabels'] ?? [])
                ->allowTransparent(! $attributes['required'] || $value === null || ! in_array($value, $presets, true))
                ->blankLabel(t('No color'))
                ->disabled($attributes['name'] === null)
                ->readOnly($attributes['readonly'])
                ->required($attributes['required'])
                ->invalid(($attributes['aria']['invalid'] ?? null) === 'true')
                ->describedBy($attributes['aria']['describedby'] ?? null)
                ->toHtml();
        }

        return InputColor::make()
            ->id($attributes['id'])
            ->name($attributes['name'])
            ->value($value)
            ->presets($control->props['presets'] ?? [])
            ->alpha($control->props['alpha'] ?? false)
            ->disabled($attributes['name'] === null)
            ->readOnly($attributes['readonly'])
            ->describedBy($attributes['aria']['describedby'] ?? null)
            ->inputAttributes([
                'required' => $attributes['required'],
                'aria' => ['invalid' => $attributes['aria']['invalid'] ?? null],
            ])
            ->toHtml();
    }

    /**
     * The preset that's the same color as `$value`, or `$value` itself when
     * none is.
     *
     * @param  list<string>  $presets
     */
    private static function matchingPreset(string $value, array $presets): string
    {
        $color = ColorRule::normalizeColor($value);

        return Arr::first($presets, fn (string $preset) => ColorRule::normalizeColor($preset) === $color) ?? $value;
    }

    public function component(): string
    {
        return 'craft:color';
    }

    /** @param list<string> $presets */
    public function presets(array $presets): static
    {
        $this->presets = $presets;

        return $this;
    }

    /**
     * Names for the presets, by color, shown when the color is picked from
     * them. A preset without one is named by its value.
     *
     * @param  array<string, string>  $labels
     */
    public function presetLabels(array $labels): static
    {
        $this->presetLabels = $labels;

        return $this;
    }

    /**
     * Whether the color can have an alpha channel, set via the color picker in browsers that support it.
     */
    public function alpha(bool $alpha = true): static
    {
        $this->alpha = $alpha;

        return $this;
    }

    /**
     * Whether a color other than the presets can be entered. When it can't,
     * the color is picked from the presets instead.
     */
    public function custom(bool $custom = true): static
    {
        $this->custom = $custom;

        return $this;
    }

    #[\Override]
    public function inputSchema(): array
    {
        if (! $this->custom) {
            return ['type' => 'string', 'enum' => $this->presets];
        }

        return ['type' => 'string', 'pattern' => $this->alpha ? '^#[0-9a-fA-F]{6}([0-9a-fA-F]{2})?$' : '^#[0-9a-fA-F]{6}$'];
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return [
            'presets' => $this->presets,
            ...($this->alpha ? ['alpha' => true] : []),
            ...($this->custom ? [] : ['custom' => false]),
            ...($this->presetLabels !== [] ? ['presetLabels' => $this->presetLabels] : []),
        ];
    }
}
