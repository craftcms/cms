<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\CommandPalette;

use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Data\AssetTransformer;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Enums\Theme;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Release\Enums\ReleaseStatus;
use CraftCms\Cms\Release\Models\Release;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\ImageTransforms;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Support\Facades\Workflows;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Translation\Locale;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\AssetIndexes;
use CraftCms\Cms\Utility\Utilities\ClearCaches;
use CraftCms\Cms\Utility\Utilities\ProjectConfig as ProjectConfigUtility;
use CraftCms\Cms\Utility\Utilities\QueueManager;
use CraftCms\Cms\Utility\Utilities\Updates;
use CraftCms\Cms\Workflow\Models\Workflow;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Foundation\MaintenanceMode;

use function CraftCms\Cms\t;

/**
 * The actions the command palette offers: Craft’s own, plus those registered
 * with {@see ActionRegistry}.
 *
 * Each one calls an endpoint that checks permissions on its own; the checks
 * here only decide what's worth offering.
 *
 * @since 6.0.0
 */
#[Scoped]
class Actions
{
    /** @var array<int|string, list<CommandPaletteItem>> */
    private array $actions = [];

    public function __construct(
        private readonly GeneralConfig $generalConfig,
        private readonly Plugins $plugins,
        private readonly MaintenanceMode $maintenanceMode,
        private readonly AssetTransformers $assetTransformers,
        private readonly Utilities $utilities,
        private readonly ProjectConfig $projectConfig,
        private readonly ActionRegistry $registry,
    ) {}

    /**
     * The actions available to the user.
     *
     * @return list<CommandPaletteItem>
     */
    public function all(CraftUser $user): array
    {
        $key = $user->getCraftUserId() ?? spl_object_id($user);

        $this->actions[$key] ??= [...$this->craftActions($user), ...$this->registry->items($user)];

        return $this->actions[$key];
    }

    /** @return list<CommandPaletteItem> */
    private function craftActions(CraftUser $user): array
    {
        return [
            ...$this->elementActions($user),
            ...$this->releaseActions($user),
            ...$this->utilityActions(),
            ...$this->preferenceActions($user),
            ...($user->isAdmin() ? $this->settingsActions() : []),
            new CommandPaletteItem()
                ->id('sign-out')
                ->label(t('Sign out'))
                ->description(t('My Account'))
                ->icon('arrow-right-from-bracket')
                ->href(Url::cpUrl('logout'))
                ->external(),
        ];
    }

    /** @return list<CommandPaletteItem> */
    private function elementActions(CraftUser $user): array
    {
        $actions = [];

        foreach (Sections::getEditableSections() as $section) {
            /** @var Section $section */
            if ($section->type === SectionType::Single || ! $user->can("createEntries:$section->uid")) {
                continue;
            }

            $actions[] = new CommandPaletteItem()
                ->id("create-entry-$section->handle")
                ->label(t('Create {section} entry', ['section' => t($section->name, category: 'site')]))
                ->description(t('Entries'))
                ->icon('plus')
                ->href(Url::cpUrl("entries/$section->handle/new"));
        }

        if (Edition::isAtLeast(Edition::Team) && $user->can('save', new User)) {
            $actions[] = new CommandPaletteItem()
                ->id('create-user')
                ->label(t('Create user'))
                ->description(t('Users'))
                ->icon('plus')
                ->href(Url::cpUrl('users/new'));
        }

        return $actions;
    }

    /** @return list<CommandPaletteItem> */
    private function releaseActions(CraftUser $user): array
    {
        if (! Edition::get()->supportsReleases()) {
            return [];
        }

        $actions = [];

        if ($user->can('manageReleases')) {
            $actions[] = new CommandPaletteItem()
                ->id('create-release')
                ->label(t('Create content release'))
                ->description(t('Releases'))
                ->icon('plus')
                ->href(route('craft.cp.releases.create'));
        }

        $releases = Release::query()
            ->whereIn('status', [ReleaseStatus::Draft, ReleaseStatus::Failed, ReleaseStatus::Scheduled])
            ->orderByDesc('dateCreated')
            ->get();

        foreach ($releases as $release) {
            /** @var Release $release */
            if ($release->status->isMutable() && $user->can('manage', $release)) {
                $actions[] = $this->deleteAction(
                    "delete-release-$release->id",
                    t('Delete {release}', ['release' => $release->name]),
                    t('Releases'),
                    route('craft.cp.releases.destroy', ['release' => $release->id]),
                    t('Delete “{name}”? Its unpublished drafts will be preserved.', ['name' => $release->name]),
                )->hint($this->releaseHint($release));
            }
        }

        if (! $user->can('publishReleases')) {
            return $actions;
        }

        foreach ($releases as $release) {
            /** @var Release $release */
            if ($user->can('publish', $release)) {
                $actions[] = new CommandPaletteItem()
                    ->id("publish-release-$release->id")
                    ->label(t('Publish {release}', ['release' => $release->name]))
                    ->hint($this->releaseHint($release))
                    ->description(t('Releases'))
                    ->icon('rocket')
                    ->view([
                        'type' => 'ui',
                        'modalUrl' => route('craft.cp.releases.publishModal', ['release' => $release->id]),
                        'actionUrl' => route('craft.cp.releases.publish', ['release' => $release->id]),
                    ]);
            }

            if ($release->status === ReleaseStatus::Draft && $user->can('publish', $release)) {
                $actions[] = new CommandPaletteItem()
                    ->id("schedule-release-$release->id")
                    ->label(t('Schedule {release}', ['release' => $release->name]))
                    ->hint($this->releaseHint($release))
                    ->description(t('Releases'))
                    ->icon('calendar')
                    ->view([
                        'type' => 'ui',
                        'modalUrl' => route('craft.cp.releases.scheduleModal', ['release' => $release->id]),
                        'actionUrl' => route('craft.cp.releases.schedule', ['release' => $release->id]),
                    ]);
            }
        }

        return $actions;
    }

    /**
     * The utilities' everyday tasks, for those allowed to use each utility.
     *
     * @return list<CommandPaletteItem>
     */
    private function utilityActions(): array
    {
        $actions = [];
        $description = t('Utilities');

        if ($this->utilities->checkAuthorization(ClearCaches::class)) {
            $actions[] = new CommandPaletteItem()
                ->id('clear-all-caches')
                ->label(t('Clear all caches'))
                ->description($description)
                ->icon('broom')
                ->action($this->utilityRequest('utilities/clear-caches', ['caches' => '*']));

            foreach (ClearCaches::cacheOptions() as $option) {
                // “Data caches” reads as “Clear data caches”, but “IDE helper” keeps its capitals.
                $label = preg_match('/^\p{Lu}\p{Ll}/u', $option['label']) ? mb_lcfirst($option['label']) : $option['label'];

                $actions[] = new CommandPaletteItem()
                    ->id("clear-cache-{$option['key']}")
                    ->label(t('Clear {cache}', ['cache' => $label]))
                    // What the cache holds, as the utility describes it, without its Markdown.
                    ->hint(isset($option['info']) ? str_replace('`', '', (string) $option['info']) : null)
                    ->description($description)
                    ->icon('broom')
                    ->action($this->utilityRequest('utilities/clear-caches', ['caches' => [$option['key']]]))
                    ->searchOnly();
            }
        }

        if ($this->utilities->checkAuthorization(QueueManager::class)) {
            array_push(
                $actions,
                new CommandPaletteItem()
                    ->id('run-queue')
                    ->label(t('Run pending queue jobs'))
                    ->description($description)
                    ->icon('play')
                    ->action(['type' => 'event', 'name' => 'craft:run-queue']),
                new CommandPaletteItem()
                    ->id('retry-failed-jobs')
                    ->label(t('Retry all failed jobs'))
                    ->description($description)
                    ->icon('rotate')
                    ->action($this->utilityRequest('utilities/queue-manager/retry-all')),
                new CommandPaletteItem()
                    ->id('release-all-jobs')
                    ->label(t('Release all jobs'))
                    ->description($description)
                    ->icon('trash')
                    ->variant('danger')
                    ->action([
                        ...$this->utilityRequest('utilities/queue-manager/release-all'),
                        'confirm' => t('Are you sure you want to release all jobs in the queue?'),
                    ]),
            );
        }

        if ($this->utilities->checkAuthorization(ProjectConfigUtility::class) && ! $this->projectConfig->readOnly) {
            $actions[] = new CommandPaletteItem()
                ->id('rebuild-project-config')
                ->label(t('Rebuild project config'))
                ->description($description)
                ->icon('arrows-rotate')
                ->action([
                    ...$this->utilityRequest('utilities/project-config/rebuild'),
                    'confirm' => t('Rebuild the project config from what’s stored in the database?'),
                ]);
        }

        if ($this->utilities->checkAuthorization(Updates::class)) {
            $actions[] = new CommandPaletteItem()
                ->id('check-for-updates')
                ->label(t('Check for updates'))
                ->description($description)
                ->icon('arrows-rotate')
                ->href(Url::cpUrl('utilities/updates'));
        }

        if ($this->utilities->checkAuthorization(AssetIndexes::class)) {
            $actions[] = new CommandPaletteItem()
                ->id('update-asset-indexes')
                ->label(t('Update asset indexes'))
                ->description($description)
                ->icon('image')
                ->href(Url::cpUrl('utilities/asset-indexes'));
        }

        return $actions;
    }

    /**
     * A `POST` to a utility's endpoint.
     *
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function utilityRequest(string $path, array $body = []): array
    {
        return array_filter([
            'type' => 'http',
            'method' => 'POST',
            'url' => Url::cpUrl($path),
            'body' => $body ?: null,
        ]);
    }

    /** A release's status, and when it's due when it has a target date: “Scheduled · 11/3/2026, 9:00 AM”. */
    private function releaseHint(Release $release): string
    {
        $status = $release->status->label();

        return $release->targetDate
            ? sprintf('%s · %s', $status, I18N::getFormatter()->asTimestamp($release->targetDate, Locale::LENGTH_SHORT))
            : $status;
    }

    /** @return list<CommandPaletteItem> */
    private function preferenceActions(CraftUser $user): array
    {
        $actions = [];
        $currentLanguage = $user->asElement()->getPreferredLanguage() ?? app()->getLocale();
        $currentLocale = $user->getPreference('locale');
        $currentWeekStartDay = (int) ($user->getPreference('weekStartDay') ?? $this->generalConfig->defaultWeekStartDay);
        $currentTheme = Theme::forUser($user->asElement());

        foreach (SelectOptions::getLanguageOptions(showLocalizedNames: true, appLocales: true) as $option) {
            if ($option['value'] === $currentLanguage) {
                continue;
            }

            $actions[] = $this->preferenceAction(
                "set-language-{$option['value']}",
                t('Set language to {language}', ['language' => $option['label']]),
                ['preferredLanguage' => $option['value']],
                'language',
            )->hint($option['data']['hint'] ?? null, $option['data']['hintLang'] ?? null)->searchOnly();
        }

        foreach (SelectOptions::getLanguageOptions(showLocalizedNames: true) as $option) {
            if ($option['value'] === $currentLocale) {
                continue;
            }

            $actions[] = $this->preferenceAction(
                "set-locale-{$option['value']}",
                t('Set formatting locale to {language}', ['language' => $option['label']]),
                ['preferredLocale' => $option['value']],
                'language',
            )->hint($option['data']['hint'] ?? null, $option['data']['hintLang'] ?? null)->searchOnly();
        }

        foreach (I18N::getLocale()->getWeekDayNames() as $day => $dayName) {
            if ($day === $currentWeekStartDay) {
                continue;
            }

            $actions[] = $this->preferenceAction(
                "set-week-start-day-$day",
                t('Set week start day to {day}', ['day' => $dayName]),
                ['weekStartDay' => $day],
                'calendar',
            );
        }

        if ($user->isAdmin()) {
            $showing = (bool) $user->getPreference('showFieldHandles');

            $actions[] = $this->preferenceAction(
                'toggle-field-handles',
                $showing ? t('Hide field handles in edit forms') : t('Show field handles in edit forms'),
                ['showFieldHandles' => ! $showing],
                'code',
            );
        }

        $themes = [
            [Theme::Light, t('Set theme to Light'), 'sun'],
            [Theme::Dark, t('Set theme to Dark'), 'moon'],
            [Theme::System, t('Set theme to System'), 'desktop'],
        ];

        foreach ($themes as [$theme, $label, $icon]) {
            if ($theme !== $currentTheme) {
                $actions[] = $this->preferenceAction("set-theme-$theme->value", $label, ['theme' => $theme->value], $icon);
            }
        }

        return $actions;
    }

    /** @param array<string, mixed> $preferences */
    private function preferenceAction(string $id, string $label, array $preferences, string $icon): CommandPaletteItem
    {
        return new CommandPaletteItem()
            ->id($id)
            ->label($label)
            ->description(t('Preferences'))
            ->icon($icon)
            ->action([
                'type' => 'http',
                'method' => 'PATCH',
                'url' => Url::cpUrl('myaccount/preferences'),
                'body' => $preferences,
            ])
            ->reload();
    }

    /** @return list<CommandPaletteItem> */
    private function settingsActions(): array
    {
        $actions = [];

        if ($this->generalConfig->allowAdminChanges) {
            $creatable = [
                ['site', t('Create site'), route('craft.cp.settings.sites.create')],
                ['route', t('Create route'), route('craft.cp.settings.routes.create')],
                ['user-group', t('Create user group'), Edition::isAtLeast(Edition::Pro) ? route('craft.cp.settings.users.groups.create') : null],
                ['section', t('Create section'), route('craft.cp.settings.sections.create')],
                ['entry-type', t('Create entry type'), route('craft.cp.settings.entry-types.create')],
                ['workflow', t('Create workflow'), Edition::isAtLeast(Edition::Pro) ? route('craft.cp.settings.workflows.create') : null],
                ['volume', t('Create asset volume'), route('craft.cp.settings.assets.volumes.create')],
                ['image-transform', t('Create image transform'), route('craft.cp.settings.assets.transforms.create')],
                ['asset-transformer', t('Create asset transformer'), route('craft.cp.settings.assets.transformers.create')],
                ['field', t('Create field'), route('craft.cp.settings.fields.create')],
            ];

            foreach ($creatable as [$id, $label, $url]) {
                if ($url !== null) {
                    $actions[] = new CommandPaletteItem()
                        ->id("create-$id")
                        ->label($label)
                        ->description(t('Settings'))
                        ->icon('plus')
                        ->href($url);
                }
            }

            array_push($actions, ...$this->pluginActions(), ...$this->settingsDeleteActions());
        }

        $actions[] = $this->maintenanceMode->active()
            ? $this->maintenanceModeAction(false)
            : $this->maintenanceModeAction(true);

        return $actions;
    }

    /**
     * A “Delete …” action for each thing configured in Settings that can be
     * deleted without further choices. Search-only, as there's one per field,
     * section, and so on. Sites are left out: deleting one means deciding
     * what happens to its content.
     *
     * @return list<CommandPaletteItem>
     */
    private function settingsDeleteActions(): array
    {
        $actions = [];

        /** @param array{string, string}|null $removesPage The edit page, and the index to go to from it. */
        $add = function (string $id, ?string $name, string $label, string $type, string $url, string $confirm, ?array $removesPage = null, ?string $hint = null) use (&$actions): void {
            if ($name === null || $name === '') {
                return;
            }

            $action = $this->deleteAction("delete-$id", $label, $type, $url, $confirm)->hint($hint)->searchOnly();

            if ($removesPage !== null) {
                $action->removesPage(...$removesPage);
            }

            $actions[] = $action;
        };

        if (Edition::isAtLeast(Edition::Pro)) {
            foreach (UserGroups::getAllGroups() as $group) {
                /** @var UserGroup $group */
                $add("user-group-$group->id", $group->name, t('Delete the {name} user group', ['name' => $group->name]), t('User Group'), route('craft.cp.settings.users.groups.destroy', ['groupId' => $group->id]), t('Are you sure you want to delete "{name}"?', ['name' => $group->name]), null, $group->handle);
            }
        }

        foreach (Sections::getAllSections() as $section) {
            /** @var Section $section */
            $add("section-$section->id", $section->name, t('Delete the {name} section', ['name' => $section->name]), t('Section'), route('craft.cp.settings.sections.destroy', ['section' => $section->id]), t('Are you sure you want to delete “{name}” and all its entries?', ['name' => $section->name]), [route('craft.cp.settings.sections.edit', ['section' => $section->id]), route('craft.cp.settings.sections.index')], $section->handle);
        }

        foreach (EntryTypes::getAllEntryTypes() as $entryType) {
            /** @var EntryType $entryType */
            $add("entry-type-$entryType->id", $entryType->name, t('Delete the {name} entry type', ['name' => $entryType->name]), t('Entry Type'), route('craft.cp.settings.entry-types.destroy', ['entryType' => $entryType->id]), t('Are you sure you want to delete “{name}” and all entries of that type?', ['name' => $entryType->name]), null, $entryType->handle);
        }

        if (Edition::isAtLeast(Edition::Pro)) {
            foreach (Workflows::getAllWorkflows() as $workflow) {
                /** @var Workflow $workflow */
                $add("workflow-$workflow->id", $workflow->name, t('Delete the {name} workflow', ['name' => $workflow->name]), t('Workflow'), route('craft.cp.settings.workflows.destroy', ['workflow' => $workflow->id]), t('Are you sure you want to delete “{name}”?', ['name' => $workflow->name]));
            }
        }

        foreach (Volumes::getAllVolumes() as $volume) {
            /** @var Volume $volume */
            $add("volume-$volume->id", $volume->name, t('Delete the {name} volume', ['name' => $volume->name]), t('Asset Volume'), route('craft.cp.settings.assets.volumes.destroy', ['volumeId' => $volume->id]), t('Are you sure you want to delete "{name}"?', ['name' => $volume->name]), [route('craft.cp.settings.assets.volumes.edit', ['volumeId' => $volume->id]), route('craft.cp.settings.assets.volumes.index')], $volume->handle);
        }

        foreach (ImageTransforms::getAllTransforms() as $transform) {
            /** @var ImageTransform $transform */
            if ($transform->id !== null && $transform->handle !== null) {
                $add("image-transform-$transform->id", $transform->name, t('Delete the {name} image transform', ['name' => $transform->name]), t('Image Transform'), route('craft.cp.settings.assets.transforms.destroy', ['transformId' => $transform->id]), t('Are you sure you want to delete the “{name}” transform?', ['name' => $transform->name]), [route('craft.cp.settings.assets.transforms.edit', ['transformHandle' => $transform->handle]), route('craft.cp.settings.assets.transforms.index')], $transform->handle);
            }
        }

        foreach ($this->assetTransformers->getAllAssetTransformers() as $transformer) {
            /** @var AssetTransformer $transformer */
            $add("asset-transformer-$transformer->handle", $transformer->name, t('Delete the {name} asset transformer', ['name' => $transformer->name]), t('Asset Transformer'), route('craft.cp.settings.assets.transformers.destroy', ['handle' => $transformer->handle]), t('Are you sure you want to delete the “{name}” Asset Transformer?', ['name' => $transformer->name]), [route('craft.cp.settings.assets.transformers.edit', ['handle' => $transformer->handle]), route('craft.cp.settings.assets.transformers.index')], $transformer->handle);
        }

        foreach (Fields::getAllFields() as $field) {
            /** @var FieldInterface $field */
            $add("field-$field->id", $field->name, t('Delete the {name} field', ['name' => $field->name]), t('Field'), route('craft.cp.settings.fields.destroy', ['fieldId' => $field->id]), t('Are you sure you want to delete “{name}”?', ['name' => $field->name]), [route('craft.cp.settings.fields.edit', ['fieldId' => $field->id]), route('craft.cp.settings.fields.index')], Pages::fieldHint($field));
        }

        return $actions;
    }

    /**
     * Deletes something by sending a `DELETE` request to its endpoint, once
     * the person has confirmed it.
     */
    private function deleteAction(string $id, string $label, string $type, string $url, string $confirm): CommandPaletteItem
    {
        return new CommandPaletteItem()
            ->id($id)
            ->label($label)
            ->description($type)
            ->icon('trash')
            ->variant('danger')
            ->action([
                'type' => 'http',
                'method' => 'DELETE',
                'url' => $url,
                'confirm' => $confirm,
            ]);
    }

    /** @return list<CommandPaletteItem> */
    private function pluginActions(): array
    {
        $actions = [];
        $anyEnabled = false;
        $anyDisabled = false;

        foreach ($this->plugins->getComposerPluginInfo() ?? [] as $handle => $info) {
            $name = $info['name'] ?? $handle;
            $installed = $this->plugins->isPluginInstalled($handle);

            if (! $installed) {
                $actions[] = $this->pluginAction($handle, 'install', t('Install the {plugin} plugin', ['plugin' => $name]), 'plug');

                continue;
            }

            if ($this->plugins->isPluginEnabled($handle)) {
                $anyEnabled = true;
                $actions[] = $this->pluginAction($handle, 'disable', t('Disable the {plugin} plugin', ['plugin' => $name]), 'circle-pause');
            } else {
                $anyDisabled = true;
                $actions[] = $this->pluginAction($handle, 'enable', t('Enable the {plugin} plugin', ['plugin' => $name]), 'circle-play');
            }

            $actions[] = $this->pluginAction($handle, 'uninstall', t('Uninstall the {plugin} plugin', ['plugin' => $name]), 'trash')
                ->variant('danger')
                ->action([
                    'type' => 'http',
                    'method' => 'POST',
                    'url' => Url::cpUrl("settings/plugins/$handle/uninstall"),
                    'confirm' => t('Are you sure you want to uninstall {plugin}? You will lose all of its associated data.', ['plugin' => $name]),
                ]);
        }

        if ($anyDisabled) {
            $actions[] = $this->bulkPluginAction('enable-all', t('Enable all plugins'), 'circle-play');
        }

        if ($anyEnabled) {
            $actions[] = $this->bulkPluginAction('disable-all', t('Disable all plugins'), 'circle-pause');
        }

        return $actions;
    }

    private function pluginAction(string $handle, string $action, string $label, string $icon): CommandPaletteItem
    {
        $version = $this->plugins->getComposerPluginInfo($handle)['version'] ?? null;

        return new CommandPaletteItem()
            ->id("$action-plugin-$handle")
            ->label($label)
            // The handle and version, which tell apart plugins with similar names.
            ->hint(is_string($version) ? "$handle · $version" : $handle)
            ->description(t('Plugins'))
            ->icon($icon)
            ->action([
                'type' => 'http',
                'method' => 'POST',
                'url' => Url::cpUrl("settings/plugins/$handle/$action"),
            ])
            ->reload();
    }

    private function bulkPluginAction(string $action, string $label, string $icon): CommandPaletteItem
    {
        return new CommandPaletteItem()
            ->id("$action-plugins")
            ->label($label)
            ->description(t('Plugins'))
            ->icon($icon)
            ->action([
                'type' => 'http',
                'method' => 'POST',
                'url' => route("craft.cp.settings.plugins.$action"),
            ])
            ->reload();
    }

    private function maintenanceModeAction(bool $enable): CommandPaletteItem
    {
        return new CommandPaletteItem()
            ->id($enable ? 'enable-maintenance-mode' : 'disable-maintenance-mode')
            ->label($enable ? t('Enable maintenance mode') : t('Disable maintenance mode'))
            ->description(t('Settings'))
            ->icon('person-digging')
            ->action([
                'type' => 'http',
                'method' => 'POST',
                'url' => route('craft.cp.settings.general.maintenance-mode'),
                'body' => ['enabled' => $enable],
            ])
            ->reload();
    }
}
