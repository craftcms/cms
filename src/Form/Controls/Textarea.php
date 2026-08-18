<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Cp\Components\Textarea as TextareaComponent;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\Controls\Concerns\HasTextExpander;
use CraftCms\Cms\Form\FormHtmlRenderer;
use Illuminate\Support\Arr;

class Textarea extends Control
{
    use HasTextExpander;

    private int $rows = 2;

    private ?int $maxLength = null;

    private ?string $placeholder = null;

    private bool $monospace = false;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        $textarea = TextareaComponent::make()
            ->id($attributes['id'])
            ->name($attributes['name'])
            ->value($value === null ? null : (string) $value)
            ->rows($control->props['rows'] ?? 2)
            ->maxlength($control->props['maxLength'] ?? null)
            ->placeholder($control->props['placeholder'] ?? null)
            ->monospace((bool) ($control->props['monospace'] ?? false))
            ->disabled($attributes['disabled'])
            ->readOnly($attributes['readonly'])
            ->describedBy($attributes['aria']['describedby'] ?? null)
            ->inputAttributes([
                'required' => $attributes['required'],
                'aria' => ['invalid' => $attributes['aria']['invalid'] ?? null],
            ])
            ->toHtml();

        return $textarea.self::textExpanderHtml($control, $attributes);
    }

    public function component(): string
    {
        return 'craft:textarea';
    }

    public function rows(int $rows): static
    {
        $this->rows = $rows;

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
            'rows' => $this->rows !== 2 ? $this->rows : null,
            'maxLength' => $this->maxLength,
            'placeholder' => $this->placeholder,
            'monospace' => $this->monospace ?: null,
            ...$this->textExpanderProps(),
        ]);
    }
}
