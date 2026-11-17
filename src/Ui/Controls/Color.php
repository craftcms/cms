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
        $presets = $control->props['presets'] ?? [];
        $custom = ($control->props['custom'] ?? true) !== false;
        $name = $attributes['name'];

        if ($presets === [] && $custom) {
            return self::colorInput($control, $attributes, $name, $value, $attributes['required'])->toHtml();
        }

        // The value as one of the presets, when it's one of their colors.
        $preset = $value !== null ? self::matchingPreset($value, $presets) : null;
        $isPreset = $preset !== null && in_array($preset, $presets, true);
        $isCustom = $value !== null && $custom && ! $isPreset;

        $select = SelectColor::make()
            ->id($attributes['id'])
            ->name($name !== null && $custom ? "{$name}[color]" : $name)
            ->value(match (true) {
                $isCustom => '__custom__',
                $preset !== null => $preset,
                default => '__blank__',
            })
            // A value that can't be a custom color is listed, so it's shown.
            ->colors($preset === null || $isPreset || $custom ? $presets : [...$presets, $preset])
            ->labels($control->props['presetLabels'] ?? [])
            ->allowTransparent(! $attributes['required'] || $value === null || (! $custom && ! $isPreset))
            ->blankLabel(t('No color'))
            ->allowCustom($custom)
            ->disabled($name === null)
            ->readOnly($attributes['readonly'])
            ->required($attributes['required'])
            ->invalid(($attributes['aria']['invalid'] ?? null) === 'true')
            ->describedBy($attributes['aria']['describedby'] ?? null);

        if (! $custom) {
            return $select->toHtml();
        }

        // "Custom…" reveals the custom color, as a field toggle.
        $customId = "{$attributes['id']}-custom";

        return $select
            ->attributes([
                'class' => 'fieldtoggle',
                'data' => ['target-prefix' => "#$customId-"],
            ])
            ->toHtml().
            // Only a custom color fills it, as nothing here keeps it in step
            // with another color picked in the select, which it would replace.
            // It isn't required: it's hidden until "Custom…" is picked, and a
            // hidden required input stops the form submitting.
            self::colorInput($control, $attributes, $name !== null ? "{$name}[custom]" : null, $isCustom ? $value : null, false)
                ->id($customId)
                ->attributes([
                    'id' => "$customId-__custom__",
                    'slot' => 'after',
                    'label' => t('Custom color:'),
                    'class' => array_filter(['mt-sm', $isCustom ? null : 'hidden']),
                ])
                ->toHtml();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private static function colorInput(ControlPayload $control, array $attributes, ?string $name, ?string $value, bool $required): InputColor
    {
        return InputColor::make()
            ->id($attributes['id'])
            ->name($name)
            ->value($value)
            ->alpha($control->props['alpha'] ?? false)
            ->disabled($attributes['name'] === null)
            ->readOnly($attributes['readonly'])
            ->describedBy($attributes['aria']['describedby'] ?? null)
            ->inputAttributes([
                'required' => $required,
                'aria' => ['invalid' => $attributes['aria']['invalid'] ?? null],
            ]);
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
        // Written as the Color field stores them, so `ff0000` is `#ff0000`,
        // and without any that aren't colors, as the color input dropped them.
        $this->presets = array_values(array_unique(array_filter(
            array_map(self::normalizeColor(...), $presets),
            fn (?string $color) => $color !== null,
        )));

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
        $this->presetLabels = [];
        foreach ($labels as $color => $label) {
            $color = self::normalizeColor((string) $color);
            if ($color !== null) {
                $this->presetLabels[$color] = $label;
            }
        }

        return $this;
    }

    /**
     * The color as {@see ColorRule::normalizeColor()} writes it, or `null` when
     * it isn't a hex color or `transparent`.
     */
    private static function normalizeColor(string $color): ?string
    {
        $color = trim($color);
        if ($color === '') {
            return null;
        }

        $color = ColorRule::normalizeColor($color);

        return $color === 'transparent' || preg_match('/^#[0-9a-f]{6}([0-9a-f]{2})?$/', $color) ? $color : null;
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
