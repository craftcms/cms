<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Controls;

use CraftCms\Cms\Cp\IconSets;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Ui\ControlPayload;
use CraftCms\Cms\Ui\UiHtmlRenderer;

/**
 * An icon-name Control. Its canonical value is a string or null.
 *
 * @since 6.0.0
 */
class IconPicker extends Control
{
    private bool $freeOnly = false;

    /** @var list<string>|string|null */
    private array|string|null $sets = null;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, UiHtmlRenderer $renderer): string
    {
        return Html::tag('craft-icon-picker', '', [
            'id' => $attributes['id'],
            'name' => $attributes['name'],
            'value' => $value === null ? null : (string) $value,
            'free-only' => $control->props['freeOnly'] ?? false,
            'sets' => isset($control->props['sets']) ? Json::encode($control->props['sets']) : null,
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

    /**
     * The icon sets that should be selectable, in order. {@see IconSets::FONT_AWESOME}
     * refers to the built-in Font Awesome icons; `'*'` offers every icon set, and
     * `null` offers Font Awesome alone.
     *
     * @param  list<string>|'*'|null  $sets
     *
     * @since 5.12.0
     */
    public function sets(array|string|null $sets): static
    {
        $this->sets = is_array($sets) ? array_values($sets) : $sets;

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return array_filter([
            'freeOnly' => $this->freeOnly,
            'sets' => $this->sets,
        ], fn (mixed $prop) => $prop !== null);
    }
}
