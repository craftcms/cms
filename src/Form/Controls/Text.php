<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Cp\Components\Input;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use Illuminate\Support\Arr;

class Text extends Control
{
    protected string $inputType = 'text';

    private string|int|float|null $min = null;

    private string|int|float|null $max = null;

    private string|int|float|null $step = null;

    private ?int $maxLength = null;

    private ?string $placeholder = null;

    private bool $monospace = false;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        return Input::make()
            ->id($attributes['id'])
            ->name($attributes['name'])
            ->value($value === null ? null : (string) $value)
            ->type($control->props['inputType'] ?? 'text')
            ->min($control->props['min'] ?? null)
            ->max($control->props['max'] ?? null)
            ->step($control->props['step'] ?? null)
            ->maxlength($control->props['maxLength'] ?? null)
            ->inputSize($control->props['size'] ?? null)
            ->placeholder($control->props['placeholder'] ?? null)
            ->monospace((bool) ($control->props['monospace'] ?? false))
            ->disabled($attributes['disabled'] || ($attributes['readonly'] && ($control->props['inputType'] ?? 'text') === 'range'))
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

    public function min(string|int|float|null $min): static
    {
        $this->min = $min;

        return $this;
    }

    public function max(string|int|float|null $max): static
    {
        $this->max = $max;

        return $this;
    }

    public function step(string|int|float|null $step): static
    {
        $this->step = $step;

        return $this;
    }

    public function maxLength(?int $maxLength): static
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    public function placeholder(?string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function monospace(bool $monospace = true): static
    {
        $this->monospace = $monospace;

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return Arr::whereNotNull([
            'inputType' => $this->inputType !== 'text' ? $this->inputType : null,
            'min' => $this->min,
            'max' => $this->max,
            'step' => $this->step,
            'maxLength' => $this->maxLength,
            'placeholder' => $this->placeholder,
            'monospace' => $this->monospace ?: null,
        ]);
    }
}
