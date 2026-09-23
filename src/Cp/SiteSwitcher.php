<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\SiteGroups;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Url;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

use function CraftCms\Cms\t;

/**
 * The control panel's site switcher: which site the CP is working with, and
 * the crumb that moves between them.
 *
 * The switcher leads the breadcrumbs on every screen rather than belonging to
 * any one of them — which site you're editing frames everything below it, the
 * same way the legacy CP's `site-crumb` does.
 */
#[Scoped]
readonly class SiteSwitcher
{
    public function __construct(
        private RequestedSite $requestedSite,
    ) {}

    /**
     * The sites the switcher offers: the ones this user may edit.
     *
     * @return Collection<int, Site>
     */
    public function sites(): Collection
    {
        return Sites::isMultiSite()
            ? Sites::getEditableSites()->values()
            : collect();
    }

    /**
     * The site crumb, or `null` when there's no switching to be done — a
     * single-site install, or a user who can only edit one of several.
     *
     * Deliberately not a link: it would only point at the page you're already
     * on, and a crumb with a URL has its menu rebuilt from whichever nav level
     * that URL sits in (`withNavCrumbMenus()` on the client), which would
     * replace these sites with the main navigation.
     */
    public function crumb(): ?ActionItem
    {
        $sites = $this->sites();

        if ($sites->count() < 2) {
            return null;
        }

        $selected = $this->requestedSite->get();

        return new ActionItem()
            ->id('site-crumb')
            ->icon('world')
            ->ariaLabel(t('Site'))
            ->label(t(($selected ?? $sites->first())->name, category: 'site'))
            ->items($this->items($sites, $selected));
    }

    /**
     * The sites as menu items, grouped by site group the way the legacy site
     * menu groups them — but only when there's more than one group to tell
     * apart.
     *
     * @param  Collection<int, Site>  $sites
     * @return list<array<string, mixed>>
     */
    private function items(Collection $sites, ?Site $selected): array
    {
        $groups = SiteGroups::getAllGroups();

        if ($groups->count() < 2) {
            return $sites->map(fn (Site $site): array => $this->item($site, $selected))->all();
        }

        return $groups
            ->map(fn ($group): array => [
                'type' => 'group',
                'heading' => t($group->name, category: 'site'),
                'items' => $sites
                    ->filter(fn (Site $site): bool => $site->groupId === $group->id)
                    ->map(fn (Site $site): array => $this->item($site, $selected))
                    ->values()
                    ->all(),
            ])
            // A group none of the editable sites belong to has nothing to head.
            ->filter(fn (array $group): bool => $group['items'] !== [])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function item(Site $site, ?Site $selected): array
    {
        return [
            'type' => 'link',
            'label' => t($site->name, category: 'site'),
            'href' => $this->url($site),
            'selected' => $site->id === $selected?->id,
        ];
    }

    /**
     * The page you're on, as the given site sees it.
     *
     * Switching sites shouldn't move you somewhere else, so the current path
     * and query are kept and only `site` is swapped. `fresh` is dropped —
     * it's a one-shot cache-buster that shouldn't be carried along.
     */
    private function url(Site $site): string
    {
        $params = Arr::except(request()->query(), ['fresh']);

        return Url::cpUrl(request()->craftPath(), ['site' => $site->handle] + $params);
    }
}
