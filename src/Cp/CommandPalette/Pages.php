<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\CommandPalette;

use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Data\AssetTransformer;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Component\Contracts\Iconic;
use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Cp\Navigation;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Release\Enums\ReleaseStatus;
use CraftCms\Cms\Release\Models\Release;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\ImageTransforms;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Support\Facades\Workflows;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\EditUserScreens;
use CraftCms\Cms\Workflow\Models\Workflow;
use Illuminate\Container\Attributes\Scoped;

use function CraftCms\Cms\t;

/**
 * The pages the command palette offers: Craft’s own, plus those registered
 * with {@see PageRegistry}.
 *
 * @since 6.0.0
 */
#[Scoped]
class Pages
{
    /** How many of the most recent unpublished releases are listed. */
    private const int RELEASE_LIMIT = 50;

    /** @var array<int|string, list<CommandPaletteItem>> */
    private array $pages = [];

    public function __construct(
        private readonly Navigation $navigation,
        private readonly EditUserScreens $editUserScreens,
        private readonly AssetTransformers $assetTransformers,
        private readonly PageRegistry $registry,
    ) {}

    /**
     * The pages available to the user.
     *
     * @return list<CommandPaletteItem>
     */
    public function all(CraftUser $user): array
    {
        $key = $user->getCraftUserId() ?? spl_object_id($user);

        $this->pages[$key] ??= [...$this->craftPages($user), ...$this->registry->items($user)];

        return $this->pages[$key];
    }

    /** @return list<CommandPaletteItem> */
    private function craftPages(CraftUser $user): array
    {
        return [
            ...$this->navPages($this->navigation->getTree()),
            ...$this->accountPages($user),
            ...$this->siteRoots(),
            ...$this->releasePages($user),
            ...($user->isAdmin() ? $this->settingsPages() : []),
        ];
    }

    /**
     * The global nav, flattened, with each page described by the items above
     * it, so “Sections” reads as being in “Settings”. A page without an icon
     * of its own takes the nearest one above it, so “All entries” shows
     * Entries’ icon.
     *
     * @param  NavItem[]  $items
     * @param  list<string>  $trail
     * @param  array{icon: string|null, iconSvg: string|null}  $parentIcon
     * @return list<CommandPaletteItem>
     */
    private function navPages(array $items, array $trail = [], array $parentIcon = ['icon' => null, 'iconSvg' => null]): array
    {
        $pages = [];

        foreach ($items as $item) {
            $label = $item->label ?? '';
            $icon = $item->icon || $item->iconSvg
                ? ['icon' => $item->icon, 'iconSvg' => $item->iconSvg]
                : $parentIcon;

            if (! $item->group && $item->href && $label !== '') {
                $pages[] = new CommandPaletteItem()
                    ->id($item->id ?? 'nav-'.md5($item->href))
                    ->label($label)
                    // A no-break space after each arrow keeps it with the item it leads to.
                    ->description($trail === [] ? null : implode(" →\u{00A0}", $trail))
                    ->icon($icon['icon'])
                    ->iconSvg($icon['iconSvg'])
                    ->href($item->href)
                    ->external($item->external)
                    ->keywords($item->keywords);
            }

            if (is_array($item->subnav) && $item->subnav !== []) {
                array_push($pages, ...$this->navPages(
                    $item->subnav,
                    // Top-level groups (“Content”, “Administration”) only organize the nav.
                    $label === '' || ($item->group && $trail === []) ? $trail : [...$trail, $label],
                    $icon,
                ));
            }
        }

        return $pages;
    }

    /** @return list<CommandPaletteItem> */
    private function accountPages(CraftUser $user): array
    {
        $element = $user->asElement();
        $pages = [];

        foreach ($this->editUserScreens->screens($element) as $screen => $info) {
            $pages[] = new CommandPaletteItem()
                ->id("myaccount-$screen")
                ->label($info['label'])
                ->description(t('My Account'))
                ->icon('user')
                ->href($info['url'] ?? $this->editUserScreens->url($element, $screen));
        }

        return $pages;
    }

    /** @return list<CommandPaletteItem> */
    private function siteRoots(): array
    {
        return Sites::getAllSites()
            ->filter(fn (Site $site) => $site->hasUrls && $site->getBaseUrl())
            ->map(fn (Site $site) => new CommandPaletteItem()
                ->id("site-root-$site->id")
                ->label($site->getName())
                ->description(t('Front end'))
                ->icon(Icons::earth())
                ->href($site->getBaseUrl())
                ->external())
            ->values()
            ->all();
    }

    /** @return list<CommandPaletteItem> */
    private function releasePages(CraftUser $user): array
    {
        if (
            ! Edition::get()->supportsReleases() ||
            (! Sections::getTotalEditableSections() && ! $user->can('manageReleases'))
        ) {
            return [];
        }

        return Release::query()
            ->whereNot('status', ReleaseStatus::Published)
            ->orderByDesc('dateCreated')
            ->limit(self::RELEASE_LIMIT)
            ->get()
            ->map(fn (Release $release) => new CommandPaletteItem()
                ->id("release-$release->id")
                ->label($release->name)
                ->description(t('Releases'))
                ->icon('rocket')
                ->href(route('craft.cp.releases.show', ['release' => $release->id])))
            ->values()
            ->all();
    }

    /**
     * The edit pages for each thing configured in Settings. Only admins can
     * reach them, and they're read-only where admin changes are disallowed.
     *
     * @return list<CommandPaletteItem>
     */
    private function settingsPages(): array
    {
        $pages = [];

        // The hint is the handle, so developers can find things by it too.
        $add = function (string $id, ?string $label, ?string $url, string $type, string $icon, ?string $hint = null, ?string $iconColor = null) use (&$pages): void {
            if ($label === null || $label === '' || $url === null) {
                return;
            }

            $pages[] = new CommandPaletteItem()
                ->id($id)
                // “settings”, as these are the things’ settings, not the things themselves.
                ->label(t('{name} settings', ['name' => $label]))
                ->hint($hint)
                ->description($type)
                ->icon($icon)
                ->iconColor($iconColor)
                ->href($url);
        };

        foreach (Sites::getAllSites(true) as $site) {
            /** @var Site $site */
            $add("site-$site->id", $site->getName(), route('craft.cp.settings.sites.edit', ['site' => $site->id]), t('Site'), Icons::earth(), $site->handle);
        }

        if (Edition::isAtLeast(Edition::Team)) {
            foreach (UserGroups::getAllGroups() as $group) {
                /** @var UserGroup $group */
                $add("user-group-$group->id", $group->name, $group->getCpEditUrl(), t('User Group'), 'users', $group->handle);
            }
        }

        foreach (Sections::getAllSections() as $section) {
            /** @var Section $section */
            $add("section-$section->id", $section->name, $section->getCpEditUrl(), t('Section'), 'newspaper', $section->handle);
        }

        foreach (EntryTypes::getAllEntryTypes() as $entryType) {
            /** @var EntryType $entryType */
            $add("entry-type-$entryType->id", $entryType->name, $entryType->getCpEditUrl(), t('Entry Type'), CommandPaletteItem::systemIcon($entryType->getIcon()) ?? 'files', $entryType->handle, $entryType->getColor()?->value);
        }

        if (Edition::isAtLeast(Edition::Pro)) {
            foreach (Workflows::getAllWorkflows() as $workflow) {
                /** @var Workflow $workflow */
                $add("workflow-$workflow->id", $workflow->name, route('craft.cp.settings.workflows.edit', ['workflow' => $workflow->id]), t('Workflow'), 'diagram-next');
            }
        }

        foreach (Volumes::getAllVolumes() as $volume) {
            /** @var Volume $volume */
            $add("volume-$volume->id", $volume->name, $volume->getCpEditUrl(), t('Asset Volume'), 'image', $volume->handle);
        }

        foreach (ImageTransforms::getAllTransforms() as $transform) {
            /** @var ImageTransform $transform */
            if ($transform->handle !== null) {
                $add("image-transform-$transform->handle", $transform->name, route('craft.cp.settings.assets.transforms.edit', ['transformHandle' => $transform->handle]), t('Image Transform'), 'crop', $transform->handle);
            }
        }

        foreach ($this->assetTransformers->getAllAssetTransformers() as $transformer) {
            /** @var AssetTransformer $transformer */
            $add("asset-transformer-$transformer->handle", $transformer->name, route('craft.cp.settings.assets.transformers.edit', ['handle' => $transformer->handle]), t('Asset Transformer'), 'wand-magic-sparkles', $transformer->handle);
        }

        foreach (Fields::getAllFields() as $field) {
            /** @var FieldInterface $field */
            $add("field-$field->id", $field->name, $field->getCpEditUrl(), t('Field'), CommandPaletteItem::systemIcon(($field instanceof Iconic ? $field->getIcon() : null) ?? $field::icon()) ?? 'pen-to-square', self::fieldHint($field));
        }

        return $pages;
    }

    /** A field's handle and type, as “heroImage · Assets”. */
    public static function fieldHint(FieldInterface $field): ?string
    {
        return $field->handle !== null ? sprintf('%s · %s', $field->handle, $field::displayName()) : null;
    }
}
