<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Html;

use CraftCms\Cms\Component\Contracts\Statusable;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use Illuminate\Container\Attributes\Singleton;

use function CraftCms\Cms\t;

#[Singleton]
readonly class StatusHtml
{
    public function statusIndicatorHtml(string $status, array $attributes = []): ?string
    {
        $attributes += [
            'color' => null,
            'label' => ucfirst($status),
        ];

        if ($status === 'draft') {
            return Html::tag('span', '', [
                'data' => ['icon' => 'draft'],
                'class' => 'icon',
                'role' => 'img',
                'aria' => [
                    'label' => sprintf('%s %s',
                        t('Status:'),
                        $attributes['label'] ?? t('Draft'),
                    ),
                ],
            ]);
        }

        if ($attributes['color'] instanceof Color) {
            $attributes['color'] = $attributes['color']->value;
        }

        return Html::tag('craft-indicator', '', Arr::merge($attributes, [
            'fill' => $attributes['color'] ?? $status,
            'label' => sprintf('%s %s', t('Status:'), $attributes['label']),
        ]));
    }

    public function componentStatusIndicatorHtml(Statusable $component): ?string
    {
        $status = $component->getStatus();

        if ($status === 'draft') {
            return $this->statusIndicatorHtml('draft');
        }

        $statusDef = $component::statuses()[$status] ?? [];

        // Just to give the `statusIndicatorHtml` clean types
        if (is_string($statusDef)) {
            $statusDef = ['label' => $statusDef];
        }

        return $this->statusIndicatorHtml($status, $statusDef);
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
            $html = $this->statusIndicatorHtml($config['color'], [
                'slot' => 'prefix',
                'label' => null,
            ]);
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
