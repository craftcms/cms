<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Controls;

use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Ui\ControlPayload;
use CraftCms\Cms\Ui\UiHtmlRenderer;

/**
 * Lists the sites an element is saved to, and lets authors add it to other sites.
 *
 * The value is a map of site IDs to statuses. If the element type has per-site
 * statuses, it includes each listed site; otherwise it only includes the sites
 * being added.
 *
 * @since 6.0.0
 */
class ElementSites extends Control
{
    /** @var list<array{id: int, name: string, url: string|null, current: bool, removable: bool}> */
    private array $sites = [];

    /** @var list<array{id: int, name: string, enabledByDefault: bool}> */
    private array $additionalSites = [];

    private bool $withStatuses = false;

    /** @var array{elementId: int, draftId: int|null}|null */
    private ?array $deleteParams = null;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, UiHtmlRenderer $renderer): string
    {
        return (string) Html::ul(array_map(
            fn (array $site) => $site['name'],
            $control->props['sites'] ?? [],
        ), [
            'id' => $attributes['id'],
        ]);
    }

    public function component(): string
    {
        return 'craft:element-sites';
    }

    /** @return array<int, bool> */
    public function emptyValue(): array
    {
        return [];
    }

    /**
     * The sites the element is saved to, with the current site first.
     *
     * `removable` sites were added to a draft, and can be deleted from it again.
     *
     * @param  list<array{id: int, name: string, url: string|null, current: bool, removable: bool}>  $sites
     */
    public function sites(array $sites): static
    {
        $this->sites = $sites;

        return $this;
    }

    /**
     * The sites the element can be added to.
     *
     * @param  list<array{id: int, name: string, enabledByDefault: bool}>  $sites
     */
    public function additionalSites(array $sites): static
    {
        $this->additionalSites = $sites;

        return $this;
    }

    public function withStatuses(bool $withStatuses = true): static
    {
        $this->withStatuses = $withStatuses;

        return $this;
    }

    /**
     * The params `elements/delete-for-site` needs to delete removable sites.
     *
     * @param  array{elementId: int, draftId: int|null}|null  $params
     */
    public function deleteParams(?array $params): static
    {
        $this->deleteParams = $params;

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return [
            'sites' => $this->sites,
            'additionalSites' => $this->additionalSites,
            'withStatuses' => $this->withStatuses,
            'deleteParams' => $this->deleteParams,
        ];
    }
}
