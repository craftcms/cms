<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Html;

use CraftCms\Cms\Cp\Components\ActionMenu;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Enums\MenuItemType;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\SiteGroups;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * @since 6.0.0
 */
#[Singleton]
readonly class MenuHtml
{
    public function __construct(
        private Sites $sites,
    ) {}

    /**
     * Renders a menu as a `<craft-action-menu>`.
     *
     * Takes the legacy disclosure menu config: `id`, `class`, `withButton`,
     * `buttonLabel`/`buttonHtml`, `autoLabel` (label the button with the
     * selected item), `hiddenLabel`, `buttonAttributes`, `buttonSpinner`,
     * `disabled` and `withSearchInput`. Without a button, `invoker` supplies the
     * element that opens it.
     *
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, mixed>  $config
     */
    public function disclosureMenu(array $items, array $config = []): string
    {
        $config = $this->disclosureMenuConfig($items, $config);

        if ($config === null) {
            return '';
        }

        $menu = ActionMenu::make()
            ->menuItems($config['items'], normalize: false)
            ->label($config['hiddenLabel'])
            ->searchable((bool) ($config['withSearchInput'] ?? false))
            ->disabled((bool) ($config['disabled'] ?? false))
            ->attributes(array_filter([
                'id' => $config['id'],
                'class' => $config['class'],
            ]));

        $invoker = $config['withButton'] ? $this->menuButtonHtml($config) : ($config['invoker'] ?? null);
        if ($invoker !== null) {
            $menu->invoker(new HtmlString((string) $invoker));
        }

        return $menu->toHtml();
    }

    /**
     * The menu config with its defaults and normalized `items`, or null when
     * there are no items and the menu should be omitted.
     *
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>|null
     */
    protected function disclosureMenuConfig(array $items, array $config): ?array
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

        $items = Collection::make($this->disclosureMenuItems($items));

        if ($config['omitIfEmpty'] && $items->isEmpty()) {
            return null;
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

        return $config;
    }

    /** @param  array<string, mixed>  $config */
    private function menuButtonHtml(array $config): string
    {
        $labelHtml = match (true) {
            isset($config['html']) => (string) $config['html'],
            isset($config['label']) => Html::encode((string) $config['label']),
            default => '',
        };
        $buttonLabelHtml = match (true) {
            isset($config['buttonHtml']) => (string) $config['buttonHtml'],
            isset($config['buttonLabel']) => Html::encode((string) $config['buttonLabel']),
            default => '',
        };
        $contentHtml = trim($labelHtml.$buttonLabelHtml);
        $iconOnly = $contentHtml === '' || ($labelHtml === '' && str_contains($buttonLabelHtml, 'craft-icon'));

        $ariaLabel = $config['hiddenLabel'];

        if ($contentHtml === '') {
            $contentHtml = Html::tag('craft-icon', '', ['name' => 'ellipsis']);
        } elseif (! $iconOnly && $ariaLabel !== null) {
            // An aria-label would hide the visible text from assistive tech
            $contentHtml = Html::tag('span', Html::encode((string) $ariaLabel), ['class' => 'sr-only']).' '.$contentHtml;
            $ariaLabel = null;
        }

        if ($config['buttonSpinner'] ?? false) {
            $contentHtml .= Html::tag('craft-spinner', '', ['class' => ['spinner', 'hidden']]);
        }

        $buttonAttributes = $config['buttonAttributes'];
        unset($buttonAttributes['removeClass']);

        return Html::tag('craft-button', $contentHtml, Arr::merge([
            'type' => 'button',
            'variant' => 'plain',
            'inherit' => true,
            'size' => 'small',
            'icon' => $iconOnly,
            'disabled' => (bool) ($config['disabled'] ?? false),
            'aria' => [
                'label' => $ariaLabel,
            ],
        ], $buttonAttributes));
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    public function disclosureMenuItems(array $items): array
    {
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

        return $items->values()->all();
    }

    /**
     * Whether an action belongs in a chip or card, as opposed to the component's
     * own editor.
     *
     * Every non-destructive action is shown by default; an item opts in or out
     * explicitly with `showInChips`. Destructive ones are held back because the
     * screen around them owns a different destructive action — a relation
     * field's Remove detaches the element rather than deleting it — and the two
     * sitting together invites picking the wrong one.
     *
     * @param  array<string, mixed>  $item
     */
    public function showsInChips(array $item): bool
    {
        return (bool) ($item['showInChips'] ?? ! ($item['destructive'] ?? false));
    }

    /** @param array<string, mixed> $config */
    public function menuItem(array $config, string $menuId): string
    {
        return template('_includes/menuitem', [
            'item' => $config,
            'menuId' => $menuId,
        ], templateMode: TemplateMode::Cp);
    }

    /**
     * @param  array<int, array<string, mixed>>|Collection<int, array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
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
     * @param  array<int,Site|array{site:Site,status?:string}>|Collection<int,Site|array{site:Site,status?:string}>|null  $sites
     * @param  array<string, mixed>  $config
     * @return list<array<string, mixed>>
     */
    public function siteMenuItems(
        array|Collection|null $sites = null,
        ?Site $selectedSite = null,
        array $config = [],
    ): array {
        $sites ??= $this->sites->getEditableSites()->all();

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
            ->keyBy(fn (array $site): int => $site['site']->id)
            ->all();

        $path = request()->craftPath();
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

            $groupSiteItems = $groupSites->map(function (Site $site) use ($sites, $path, $params, $selectedSite) {
                if (isset($params['returnUrl']) && is_string($params['returnUrl'])) {
                    // swap the `site` param in the `returnUrl` URL too
                    $params['returnUrl'] = ElementHelper::returnUrlForSite($params['returnUrl'], $site);
                }

                return [
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
                ];
            });

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
