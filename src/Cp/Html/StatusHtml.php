<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Html;

use CraftCms\Cms\Component\Contracts\Statusable;
use CraftCms\Cms\Cp\Components\Indicator;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Support\Html;
use Illuminate\Container\Attributes\Singleton;

use function CraftCms\Cms\t;

#[Singleton]
readonly class StatusHtml
{
    public function statusIndicatorHtml(string $status, array $attributes = []): ?string
    {
        $color = $attributes['color'] ?? null;
        $label = array_key_exists('label', $attributes) ? $attributes['label'] : ucfirst($status);
        unset($attributes['color'], $attributes['label']);

        return Indicator::forStatus($status, ['color' => $color, 'label' => $label], $attributes)->toHtml();
    }

    public function componentStatusIndicatorHtml(Statusable $component): ?string
    {
        $status = $component->getStatus();

        if ($status === null) {
            return null;
        }

        $statusDef = $component::statuses()[$status] ?? [];

        // Just to give `Indicator::forStatus()` clean types
        if (is_string($statusDef)) {
            $statusDef = ['label' => $statusDef];
        }

        return Indicator::forStatus($status, $statusDef)->toHtml();
    }

    public function statusLabelHtml(array $config = []): ?string
    {
        $config += [
            'color' => Color::Gray->value,
            'icon' => null,
            'label' => null,
            'indicatorClass' => null,
        ];

        if ($config['color'] instanceof Color) {
            $config['color'] = $config['color']->value;
        }

        if ($config['icon']) {
            $html = Html::tag('craft-icon', '', [
                'slot' => 'prefix',
                'name' => $config['icon'],
            ]);
        } else {
            $html = Indicator::make()
                ->fill($config['color'])
                ->attributes(['slot' => 'prefix'])
                ->toHtml();
        }

        if ($config['label']) {
            $html .= Html::encode($config['label']);
        }

        return Html::tag('craft-status-badge', $html, [
            'data-color' => $config['color'],
        ]);
    }

    public function componentStatusLabelHtml(Statusable $component): ?string
    {
        $status = $component->getStatus();

        if (! $status) {
            return null;
        }

        $config = $component::statuses()[$status] ?? [];
        if (is_string($config)) {
            $config = ['label' => $config];
        }
        $config['color'] ??= Color::tryFromStatus($status) ?? Color::Gray;
        $config['label'] ??= match ($status) {
            'draft' => t('Draft'),
            default => ucfirst($status),
        };
        $config['indicatorClass'] = match ($status) {
            'pending', 'off', 'suspended', 'expired', 'disabled', 'inactive' => $status,
            default => $config['color']->value,
        };

        return $this->statusLabelHtml($config);
    }

    public function editedStatusLabelHtml(): string
    {
        return $this->statusLabelHtml([
            'color' => Color::Blue,
            'icon' => 'pen-circle',
            'label' => t('Edited'),
        ]);
    }
}
