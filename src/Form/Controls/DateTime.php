<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Cp\Components\InputDateTime;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Support\Facades\I18N;
use Illuminate\Support\Arr;

class DateTime extends Control
{
    private bool $showDate = true;

    private bool $showTime = false;

    private bool $showTimeZone = false;

    private ?string $min = null;

    private ?string $max = null;

    private int $minuteIncrement = 30;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        $value = is_array($value) ? $value : [];

        return InputDateTime::make()
            ->id($attributes['id'])
            ->name($attributes['name'])
            ->dateValue($value['date'] ?? null)
            ->timeValue($value['time'] ?? null)
            ->timezone($value['timezone'] ?? null)
            ->locale($control->props['locale'])
            ->showDate($control->props['showDate'])
            ->showTime($control->props['showTime'])
            ->showTimezone($control->props['showTimeZone'])
            ->min($control->props['min'] ?? null)
            ->max($control->props['max'] ?? null)
            ->minuteIncrement($control->props['minuteIncrement'])
            ->disabled($attributes['disabled'])
            ->readOnly($attributes['readonly'])
            ->required($attributes['required'])
            ->toHtml();
    }

    public function component(): string
    {
        return 'craft:date-time';
    }

    public function showDate(bool $showDate = true): static
    {
        $this->showDate = $showDate;

        return $this;
    }

    public function showTime(bool $showTime = true): static
    {
        $this->showTime = $showTime;

        return $this;
    }

    public function showTimeZone(bool $showTimeZone = true): static
    {
        $this->showTimeZone = $showTimeZone;

        return $this;
    }

    public function min(?string $min): static
    {
        $this->min = $min;

        return $this;
    }

    public function max(?string $max): static
    {
        $this->max = $max;

        return $this;
    }

    public function minuteIncrement(int $minuteIncrement): static
    {
        $this->minuteIncrement = $minuteIncrement;

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return Arr::whereNotNull([
            'showDate' => $this->showDate,
            'showTime' => $this->showTime,
            'showTimeZone' => $this->showTimeZone,
            'locale' => I18N::getFormattingLocale()->id,
            'min' => $this->min,
            'max' => $this->max,
            'minuteIncrement' => $this->minuteIncrement,
        ]);
    }
}
