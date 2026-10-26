<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Data;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Element\Contracts\ElementInterface;
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

    /**
     * Returns the table column config for choosing a site’s template or route.
     *
     * @param  class-string<ElementInterface>|null  $elementType  The element type being routed, used to filter
     *                                                            controller action suggestions.
     * @return array{heading: string, type: string, code: bool, options: list<array<string, mixed>>}
     */
    public static function destinationColumn(?string $elementType = null): array
    {
        return [
            'heading' => t('Route To'),
            'type' => 'template',
            'code' => true,
            'options' => [
                ...SelectOptions::getTemplateSuggestions(),
                ...SelectOptions::getRouteSuggestions($elementType),
            ],
        ];
    }

    /** @return array{uriFormat: string, destination: string} */
    public function toForm(): array
    {
        return [
            'uriFormat' => $this->uriFormat ?? '',
            'destination' => $this->route ?? $this->template ?? '',
        ];
    }

    /**
     * Applies posted site settings.
     *
     * A `destination` value is resolved to a template or route via {@see ElementRoute::isRoute()}.
     * Otherwise, explicit `template` and `route` values are used as-is.
     *
     * @param  array<string, mixed>  $data
     */
    public function applyForm(array $data): void
    {
        if (array_key_exists('destination', $data)) {
            $destination = trim((string) $data['destination']);
            $isRoute = $destination !== '' && ElementRoute::isRoute($destination);
            $template = $destination !== '' && ! $isRoute ? $destination : null;
            $route = $isRoute ? $destination : null;
        } else {
            $template = $data['template'] ?? null;
            $route = $data['route'] ?? null;
        }

        self::configure($this, [
            'uriFormat' => $data['uriFormat'] ?? null,
            'template' => $template,
            'route' => $route,
        ]);
    }
}
