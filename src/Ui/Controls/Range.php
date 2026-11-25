<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Controls;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Ui\ControlPayload;
use CraftCms\Cms\Ui\UiHtmlRenderer;

/**
 * @since 6.0.0
 */
class Range extends Text
{
    #[\Override]
    protected string $inputType = 'range';

    #[\Override]
    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, UiHtmlRenderer $renderer): string
    {
        return FormFields::rangeHtml([
            'id' => $attributes['id'],
            'name' => $attributes['name'],
            'value' => $value,
            'min' => $control->props['min'] ?? null,
            'max' => $control->props['max'] ?? null,
            'step' => $control->props['step'] ?? 1,
            'suffix' => $control->props['suffix'] ?? false,
            'size' => $control->props['size'] ?? null,
            'orientation' => $control->props['dir'] ?? null,
            'disabled' => $attributes['disabled'] || $attributes['readonly'],
            'describedBy' => $attributes['aria']['describedby'] ?? null,
        ]);
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        $props = parent::props($value);
        $props['size'] ??= self::inputSize($props['min'] ?? null, $props['max'] ?? null, $props['step'] ?? null);

        return $props;
    }

    /**
     * The number of characters the longest value in the range can have, for
     * sizing the number input beside the slider.
     *
     * @internal
     */
    public static function inputSize(mixed $min, mixed $max, mixed $step): ?int
    {
        if (! is_numeric($max)) {
            return null;
        }

        $decimals = max(self::decimals($max), self::decimals($step));
        $integerLength = fn (mixed $number): int => strlen((string) (int) $number);
        $length = max($integerLength($max), is_numeric($min) && $min < 0 ? $integerLength($min) : 0);

        return $length + ($decimals > 0 ? $decimals + 1 : 0);
    }

    private static function decimals(mixed $number): int
    {
        if (! is_numeric($number)) {
            return 0;
        }

        $fraction = strstr((string) $number, '.');

        return $fraction === false ? 0 : strlen(rtrim(substr($fraction, 1), '0'));
    }

    #[\Override]
    public function component(): string
    {
        return 'craft:range';
    }
}
