<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Data;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Route\ElementRoute;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class ElementSiteSettings extends Component
{
    public ?string $uriFormat = null;

    public ?string $template = null;

    public ?string $route = null {
        set => $value === null ? null : ElementRoute::normalize($value);
    }

    /** @return array{heading: string, type: string, code: bool, prefixSelect: array{key: string, label: string, options: list<array{label: string, value: string}>}} */
    public static function routeColumn(): array
    {
        return [
            'heading' => t('Route'),
            'type' => 'singleline',
            'code' => true,
            'prefixSelect' => [
                'key' => 'routeType',
                'label' => t('Route type'),
                'options' => [
                    ['label' => t('Template'), 'value' => 'template'],
                    ['label' => t('Route'), 'value' => 'route'],
                ],
            ],
        ];
    }

    /** @return array{uriFormat: string, routeType: 'template'|'route', route: string} */
    public function toForm(): array
    {
        return [
            'uriFormat' => $this->uriFormat ?? '',
            'routeType' => empty($this->route) ? 'template' : 'route',
            'route' => $this->route ?? $this->template ?? '',
        ];
    }

    /** @param array<string, mixed> $data */
    public function applyForm(array $data): void
    {
        $routeType = $data['routeType'] ?? null;

        self::configure($this, [
            'uriFormat' => $data['uriFormat'] ?? null,
            'template' => $routeType === 'template'
                ? ($data['route'] ?? null)
                : ($routeType === 'route' ? null : ($data['template'] ?? null)),
            'route' => $routeType === 'template' ? null : ($data['route'] ?? null),
        ]);
    }
}
