<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Html;

use CraftCms\Cms\Element\Enums\MenuItemType;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\SiteGroups;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

#[Singleton]
readonly class MenuHtml
{
    public function __construct(
        private Sites $sites,
    ) {}

    public function disclosureMenu(array $items, array $config = []): string
    {
        $config += [
            'id' => sprintf('menu-%s', mt_rand()),
            'class' => null,
            'withButton' => true,
            'buttonLabel' => null,
            'buttonHtml' => null,
            'autoLabel' => false,
            'buttonAttributes' => [],
            'hiddenLabel' => null,
            'omitIfEmpty' => true,
        ];

        // Item normalization & cleanup
        $items = Collection::make($this->normalizeMenuItems($items));

        // Place all the destructive items at the end
        $destructiveItems = $items->filter(fn (array $item) => $item['destructive'] ?? false);
        $items = $items->reject(fn (array $item): bool => (bool) ($item['destructive'] ?? false))
            ->push(['type' => MenuItemType::HR->value])
            ->push(...$destructiveItems->all());

        // Remove leading/trailing/repetitive HRs
        while (($items->first()['type'] ?? null) === MenuItemType::HR->value) {
            $items->shift();
        }
        while (($items->last()['type'] ?? null) === MenuItemType::HR->value) {
            $items->pop();
        }
        $items = $items->values();
        $items = $items->filter(fn (array $item, int $i) => (
            ($item['type'] ?? null) !== MenuItemType::HR->value ||
            ($items->get($i + 1)['type'] ?? null) !== MenuItemType::HR->value
        ));

        // If we're left without any items, just return an empty string
        if ($config['omitIfEmpty'] && $items->isEmpty()) {
            return '';
        }

        $config['items'] = $items->all();

        if ($config['withButton'] && $config['autoLabel']) {
            // Find the selected item, also looking within nested item groups
            $selectedItem = $items
                ->map(fn (array $i) => $i['type'] === MenuItemType::Group->value ? ($i['items'] ?? []) : [$i])
                ->flatten(1)
                ->first(fn (array $i) => $i['selected'] ?? false);

            if ($selectedItem) {
                $config['label'] = $selectedItem['label'] ?? null;
                $config['html'] = $selectedItem['html'] ?? null;
            }
        }

        return template('_includes/disclosuremenu', $config, templateMode: TemplateMode::Cp);
    }

    public function menuItem(array $config, string $menuId): string
    {
        return template('_includes/menuitem', [
            'item' => $config,
            'menuId' => $menuId,
        ], templateMode: TemplateMode::Cp);
    }

    public function normalizeMenuItems(array|Collection $items): array
    {
        if ($items instanceof Collection) {
            $items = $items->all();
        }

        return array_map(function (array $item) {
            if (! isset($item['type'])) {
                if (isset($item['url'])) {
                    $item['type'] = MenuItemType::Link;
                } elseif ($item['hr'] ?? false) {
                    $item['type'] = MenuItemType::HR;
                } elseif (isset($item['heading']) || isset($item['items'])) {
                    $item['type'] = MenuItemType::Group;
                } else {
                    $item['type'] = MenuItemType::Button;
                }
            }

            if ($item['type'] instanceof MenuItemType) {
                $item['type'] = $item['type']->value;
            }

            if ($item['type'] === MenuItemType::Group->value) {
                $item['items'] = $this->normalizeMenuItems($item['items'] ?? []);
            }

            return $item;
        }, $items);
    }

    /**
     * @param  array<int,Site|array{site:Site,status?:string}>  $sites
     */
    public function siteMenuItems(
        array|Collection|null $sites = null,
        ?Site $selectedSite = null,
        array $config = [],
    ): array {
        if ($sites === null) {
            $sites = $this->sites->getEditableSites()->all();
        }

        $config += [
            'showSiteGroupHeadings' => null,
            'includeOmittedSites' => false,
        ];

        $items = [];

        $siteGroups = SiteGroups::getAllGroups();
        $config['showSiteGroupHeadings'] ??= count($siteGroups) > 1;

        // Normalize and index the sites
        /** @var array<int,array{site:Site,status?:string}> $sites */
        $sites = collect($sites)
            ->map(fn (Site|array $site) => $site instanceof Site ? ['site' => $site] : $site)
            ->keyBy(fn (array $site) => $site['site']->id)
            ->all();

        $path = request()->path();
        $params = Arr::except(request()->query(), 'fresh');

        $totalSites = 0;

        foreach ($siteGroups as $siteGroup) {
            $groupSites = $siteGroup->getSites();
            if (! $config['includeOmittedSites']) {
                $groupSites = $groupSites->filter(fn (Site $site) => isset($sites[$site->id]));
            }

            if ($groupSites->isEmpty()) {
                continue;
            }

            $totalSites += count($groupSites);

            $groupSiteItems = $groupSites->map(fn (Site $site) => [
                'status' => $sites[$site->id]['status'] ?? null,
                'label' => t($site->getName(), category: 'site'),
                'url' => Url::cpUrl($path, ['site' => $site->handle] + $params),
                'hidden' => ! isset($sites[$site->id]),
                'selected' => $site->id === $selectedSite?->id,
                'attributes' => [
                    'data' => [
                        'site-id' => $site->id,
                    ],
                ],
            ]);

            if ($config['showSiteGroupHeadings']) {
                $items[] = [
                    'heading' => t($siteGroup->name, category: 'site'),
                    'items' => $groupSiteItems,
                    'hidden' => $groupSiteItems->doesntContain(fn (array $item) => ! $item['hidden']),
                ];
            } else {
                array_push($items, ...$groupSiteItems);
            }
        }

        return $totalSites > 1 ? $items : [];
    }
}
