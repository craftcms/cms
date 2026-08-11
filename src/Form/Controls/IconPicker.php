<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Support\Html;

/** An icon-name Control. Its canonical value is a string or null. */
class IconPicker extends Control
{
    private bool $freeOnly = false;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        return Html::tag('craft-icon-picker', '', [
            'id' => $attributes['id'],
            'name' => $attributes['name'],
            'value' => $value === null ? null : (string) $value,
            'free-only' => $control->props['freeOnly'] ?? false,
            'disabled' => $attributes['name'] === null,
            'aria' => [
                'invalid' => $attributes['aria']['invalid'] ?? null,
                'describedby' => $attributes['aria']['describedby'] ?? null,
            ],
        ]);
    }

    public function component(): string
    {
        return 'craft:icon-picker';
    }

    public function freeOnly(bool $freeOnly = true): static
    {
        $this->freeOnly = $freeOnly;

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return ['freeOnly' => $this->freeOnly];
    }
}
