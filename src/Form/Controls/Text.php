<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Cp\Components\Input;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use Illuminate\Support\Arr;

class Text extends Control
{
    private string $inputType = 'text';

    private int|float|null $min = null;

    private ?int $maxLength = null;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        return Input::make()
            ->id($attributes['id'])
            ->name($attributes['name'])
            ->value($value === null ? null : (string) $value)
            ->type($control->props['inputType'] ?? 'text')
            ->min($control->props['min'] ?? null)
            ->maxlength($control->props['maxLength'] ?? null)
            ->disabled($attributes['disabled'])
            ->readOnly($attributes['readonly'])
            ->describedBy($attributes['aria']['describedby'] ?? null)
            ->attributes(['required' => $attributes['required']])
            ->inputAttributes([
                'required' => $attributes['required'],
                'aria' => ['invalid' => $attributes['aria']['invalid'] ?? null],
            ])
            ->toHtml();
    }

    public function component(): string
    {
        return 'craft:text';
    }

    public function inputType(string $inputType): static
    {
        $this->inputType = $inputType;

        return $this;
    }

    public function min(int|float|null $min): static
    {
        $this->min = $min;

        return $this;
    }

    public function maxLength(?int $maxLength): static
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    #[\Override]
    public function props(): array
    {
        return Arr::whereNotNull([
            'inputType' => $this->inputType !== 'text' ? $this->inputType : null,
            'min' => $this->min,
            'maxLength' => $this->maxLength,
        ]);
    }
}
