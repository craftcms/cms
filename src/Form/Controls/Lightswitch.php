<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Cp\Components\Lightswitch as LightswitchComponent;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use Illuminate\Support\Arr;

class Lightswitch extends Control
{
    private bool $indeterminate = false;

    private string $size = 'medium';

    private string $checkedValue = '1';

    private string $indeterminateValue = '-';

    private ?string $onLabel = null;

    private ?string $offLabel = null;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        $props = $control->props;
        $editable = $attributes['name'] !== null;

        return LightswitchComponent::make()
            ->id($attributes['id'])
            ->name($attributes['name'])
            ->on((bool) $value)
            ->indeterminate((bool) ($props['indeterminate'] ?? false))
            ->disabled(! $editable)
            ->size($props['size'] ?? 'medium')
            ->value($props['checkedValue'] ?? '1')
            ->indeterminateValue($props['indeterminateValue'] ?? '-')
            ->onLabel($props['onLabel'] ?? null)
            ->offLabel($props['offLabel'] ?? null)
            ->describedBy($attributes['aria']['describedby'] ?? null)
            ->attributes(['required' => $attributes['required']])
            ->buttonAttributes([
                'aria' => [
                    'invalid' => $attributes['aria']['invalid'] ?? null,
                    'required' => $attributes['required'] ? 'true' : null,
                ],
            ])
            ->toHtml();
    }

    public function component(): string
    {
        return 'craft:lightswitch';
    }

    public function indeterminate(bool $indeterminate = true): static
    {
        $this->indeterminate = $indeterminate;

        return $this;
    }

    /** @param 'small'|'medium' $size */
    public function size(string $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function checkedValue(string|int|float $checkedValue): static
    {
        $this->checkedValue = (string) $checkedValue;

        return $this;
    }

    public function indeterminateValue(string $indeterminateValue): static
    {
        $this->indeterminateValue = $indeterminateValue;

        return $this;
    }

    public function onLabel(?string $onLabel): static
    {
        $this->onLabel = $onLabel;

        return $this;
    }

    public function offLabel(?string $offLabel): static
    {
        $this->offLabel = $offLabel;

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return Arr::whereNotNull([
            'indeterminate' => $this->indeterminate ?: null,
            'size' => $this->size !== 'medium' ? $this->size : null,
            'checkedValue' => $this->checkedValue !== '1' ? $this->checkedValue : null,
            'indeterminateValue' => $this->indeterminateValue !== '-' ? $this->indeterminateValue : null,
            'onLabel' => $this->onLabel,
            'offLabel' => $this->offLabel,
        ]);
    }
}
