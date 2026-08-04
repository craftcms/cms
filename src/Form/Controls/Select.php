<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Cp\Components\Select as SelectComponent;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;

class Select extends Control
{
    /** @var list<array{label: string, value: bool|float|int|string}> */
    private array $options = [];

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        return SelectComponent::make()
            ->id($attributes['id'])
            ->name($attributes['name'])
            ->value($value === null ? null : (string) $value)
            ->options($control->props['options'])
            ->disabled($attributes['name'] === null)
            ->required($attributes['required'])
            ->describedBy($attributes['aria']['describedby'] ?? null)
            ->selectAttributes([
                'aria' => ['invalid' => $attributes['aria']['invalid'] ?? null],
            ])
            ->toHtml();
    }

    public function component(): string
    {
        return 'craft:select';
    }

    /** @param list<array{label: string, value: bool|float|int|string}> $options */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    #[\Override]
    public function props(): array
    {
        return ['options' => $this->options];
    }
}
