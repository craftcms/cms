<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Cp\Components\Combobox as ComboboxComponent;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use Illuminate\Support\Arr;

class Combobox extends Control
{
    /** @var list<array<string, mixed>> */
    private array $options = [];

    private ?string $placeholder = null;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        return ComboboxComponent::make()
            ->id($attributes['id'])
            ->name($attributes['name'])
            ->value($value === null ? null : (string) $value)
            ->options($control->props['options'])
            ->placeholder($control->props['placeholder'] ?? null)
            ->disabled($attributes['disabled'])
            ->readOnly($attributes['readonly'])
            ->required($attributes['required'])
            ->describedBy($attributes['aria']['describedby'] ?? null)
            ->attributes([
                'aria' => ['invalid' => $attributes['aria']['invalid'] ?? null],
            ])
            ->toHtml();
    }

    public function component(): string
    {
        return 'craft:combobox';
    }

    /** @param list<array<string, mixed>> $options */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function placeholder(?string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return Arr::whereNotNull([
            'options' => $this->options,
            'placeholder' => $this->placeholder,
        ]);
    }
}
