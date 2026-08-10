<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Cp\Components\InputMoney;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Support\Facades\I18N;
use Illuminate\Support\Arr;

class Money extends Control
{
    private string $currency = 'USD';

    private ?string $locale = null;

    private int|float|null $min = null;

    private int|float|null $max = null;

    private int|float|null $step = null;

    private ?int $size = null;

    private bool $showCurrency = true;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        $value = is_array($value) ? $value : ['value' => $value, 'locale' => $control->props['locale']];

        return InputMoney::make()
            ->id($attributes['id'])
            ->name($attributes['name'])
            ->value(isset($value['value']) ? (string) $value['value'] : null)
            ->currency($control->props['currency'])
            ->locale((string) ($value['locale'] ?? $control->props['locale']))
            ->showCurrency((bool) ($control->props['showCurrency'] ?? true))
            ->inputSize($control->props['size'] ?? null)
            ->min($control->props['min'] ?? null)
            ->max($control->props['max'] ?? null)
            ->step($control->props['step'] ?? null)
            ->inputmode('decimal')
            ->disabled($attributes['disabled'])
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
        return 'craft:money';
    }

    public function currency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function locale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function min(int|float|null $min): static
    {
        $this->min = $min;

        return $this;
    }

    public function max(int|float|null $max): static
    {
        $this->max = $max;

        return $this;
    }

    public function step(int|float|null $step): static
    {
        $this->step = $step;

        return $this;
    }

    public function size(?int $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function showCurrency(bool $showCurrency = true): static
    {
        $this->showCurrency = $showCurrency;

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return Arr::whereNotNull([
            'currency' => $this->currency,
            'locale' => $this->locale ?? I18N::getFormattingLocale()->id,
            'min' => $this->min,
            'max' => $this->max,
            'step' => $this->step,
            'size' => $this->size,
            'showCurrency' => $this->showCurrency,
        ]);
    }
}
