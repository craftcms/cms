<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Html;

use CraftCms\Cms\Component\Contracts\Statusable;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use Illuminate\Container\Attributes\Singleton;

use function CraftCms\Cms\t;

#[Singleton]
readonly class StatusHtml
{
    /** @param array<string, mixed> $attributes */
    public function statusIndicatorHtml(string $status, array $attributes = []): ?string
    {
        $label = Arr::get($attributes, 'label', ucfirst($status));

        if ($status === 'draft') {
            return Html::tag('span', '', [
                'data' => ['icon' => 'draft'],
                'class' => 'icon',
                'role' => 'img',
                'aria' => [
                    'label' => sprintf('%s %s',
                        t('Status:'),
                        $label ?? t('Draft'),
                    ),
                ],
            ]);
        }

        $color = Arr::get($attributes, 'color') ?? Color::tryFromStatus($status) ?? Color::Gray;
        $attributes = [];

        if ($color instanceof Color) {
            $color = $color->value;
        }

        $attributes['label'] = $label ? sprintf('%s %s', t('Status:'), $label) : null;
        $attributes['fill'] = $color;

        return Html::tag('craft-indicator', '', $attributes);
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

    /** @param array<string, mixed> $config */
    public function statusLabelHtml(array $config = []): ?string
    {
        $config += [
            'color' => Color::Gray->value,
            'icon' => null,
            'label' => null,
        ];

        if ($config['color'] instanceof Color) {
            $config['color'] = $config['color']->value;
        }

        // An icon, when supplied, replaces the badge's default indicator dot
        // through the `prefix` slot.
        $contents = '';
        if ($config['icon']) {
            $contents .= Html::tag('craft-icon', '', array_merge(
                Icons::resolveIconData($config['icon']),
                ['slot' => 'prefix'],
            ));
        }

        if ($config['label']) {
            $contents .= Html::encode($config['label']);
        }

        return Html::tag('craft-badge', $contents, [
            'fill' => $config['color'],
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
