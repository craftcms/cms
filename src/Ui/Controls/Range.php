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
            'orientation' => $control->props['dir'] ?? null,
            'disabled' => $attributes['disabled'] || $attributes['readonly'],
            'describedBy' => $attributes['aria']['describedby'] ?? null,
        ]);
    }

    #[\Override]
    public function component(): string
    {
        return 'craft:range';
    }
}
