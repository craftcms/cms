<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Cp\Components\Checkbox as CheckboxComponent;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use Illuminate\Support\Arr;

/**
 * A boolean checkbox that carries its own visible label, for compact contexts
 * such as a [[\CraftCms\Cms\Form\Nodes\Field::actions()]] slot.
 */
class Checkbox extends Control
{
    private ?string $label = null;

    private string $checkedValue = '1';

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        $props = $control->props;

        return CheckboxComponent::make()
            ->id($attributes['id'])
            ->name($attributes['name'])
            ->label($props['label'] ?? null)
            ->checked((bool) $value)
            ->value($props['checkedValue'] ?? '1')
            ->disabled($attributes['name'] === null)
            ->describedBy($attributes['aria']['describedby'] ?? null)
            ->inputAttributes([
                'aria' => ['invalid' => $attributes['aria']['invalid'] ?? null],
                'required' => $attributes['required'],
            ])
            ->toHtml();
    }

    public function component(): string
    {
        return 'craft:checkbox';
    }

    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function checkedValue(string|int|float $checkedValue): static
    {
        $this->checkedValue = (string) $checkedValue;

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return Arr::whereNotNull([
            'label' => $this->label,
            'checkedValue' => $this->checkedValue !== '1' ? $this->checkedValue : null,
        ]);
    }
}
