<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Controls;

use CraftCms\Cms\Cp\Components\InputColor;
use CraftCms\Cms\Ui\ControlPayload;
use CraftCms\Cms\Ui\UiHtmlRenderer;

/**
 * @since 6.0.0
 */
class Color extends Control
{
    /** @var list<string> */
    private array $presets = [];

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, UiHtmlRenderer $renderer): string
    {
        return InputColor::make()
            ->id($attributes['id'])
            ->name($attributes['name'])
            ->value($value === null ? null : (string) $value)
            ->presets($control->props['presets'] ?? [])
            ->disabled($attributes['name'] === null)
            ->readOnly($attributes['readonly'])
            ->describedBy($attributes['aria']['describedby'] ?? null)
            ->inputAttributes([
                'required' => $attributes['required'],
                'aria' => ['invalid' => $attributes['aria']['invalid'] ?? null],
            ])
            ->toHtml();
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

    #[\Override]
    public function props(mixed $value = null): array
    {
        return ['presets' => $this->presets];
    }
}
