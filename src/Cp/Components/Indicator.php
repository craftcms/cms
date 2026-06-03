<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Component\Contracts\Statusable;
use CraftCms\Cms\Cp\Components\Generated\IndicatorComponent;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

use function CraftCms\Cms\t;

/**
 * PHP counterpart to the `<craft-indicator>` web component.
 *
 * The fluent `fill`/`size`/`appearance`/`label` API is generated from the
 * custom element manifest in {@see IndicatorComponent}; this class adds the
 * control-panel status helpers on top.
 */
class Indicator extends IndicatorComponent
{
    /**
     * Builds the control-panel status indicator for a given status.
     *
     * Most statuses render as a colored dot. The special `draft` status renders
     * the draft icon instead. The accessible label is prefixed with "Status:".
     *
     * @param  array{label?: string|null, color?: string|Color|null}  $def  Status definition (see {@see Statusable::statuses()}).
     * @param  array<string, mixed>  $attributes  Additional HTML attributes for the host element.
     */
    public static function forStatus(string $status, array $def = [], array $attributes = []): Htmlable
    {
        $label = array_key_exists('label', $def) ? $def['label'] : ucfirst($status);

        if ($status === 'draft') {
            return new HtmlString(Html::tag('span', '', Arr::merge($attributes, [
                'data' => ['icon' => 'draft'],
                'class' => 'icon',
                'role' => 'img',
                'aria' => [
                    'label' => sprintf('%s %s', t('Status:'), $label ?? t('Draft')),
                ],
            ])));
        }

        return static::make()
            ->fill(($def['color'] ?? null) ?? $status)
            ->label(sprintf('%s %s', t('Status:'), $label))
            ->attributes($attributes);
    }

    /**
     * Convenience for {@see IndicatorComponent::appearance()} — renders the hollow (ring-only) variant.
     */
    public function empty(bool $empty = true): static
    {
        return $this->appearance($empty ? 'empty' : 'solid');
    }
}
