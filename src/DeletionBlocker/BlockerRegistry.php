<?php

declare(strict_types=1);

namespace CraftCms\Cms\DeletionBlocker;

use Closure;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\DeletionBlockers\Contracts\DeletionBlockerInterface;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Assets as AssetsField;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Entries as EntriesField;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\User\Data\UserGroup;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

use function CraftCms\Cms\t;

/**
 * Registers the providers of deletion blockers for each subject type in
 * {@see SubjectTypeRegistry}. The control panel shows these blockers before it
 * deletes a subject.
 *
 * Plugins may add blockers to any subject type from their service provider:
 *
 * ```php
 * public function boot(BlockerRegistry $blockers): void
 * {
 *     $blockers->register('volume', fn (Volume $volume) => [
 *         new MyVolumeBlocker($volume),
 *     ]);
 * }
 * ```
 *
 * Element types define their own blockers with
 * {@see ElementInterface::deletionBlockers()}.
 *
 * @since 6.0.0
 */
#[Singleton]
class BlockerRegistry
{
    /**
     * @var array<string, list<Closure(mixed): iterable<DeletionBlockerInterface>>>
     */
    private array $providers = [];

    public function __construct(
        private readonly SubjectTypeRegistry $subjectTypes,
    ) {
        $this->register('entry-type', fn (EntryType $entryType) => [
            new EntryTypeUsageBlocker($entryType),
            new TrashedElementsBlocker(
                Entry::class,
                ['typeId' => $entryType->id],
                fn (int $num) => t('{num, number} {num, plural, =1{entry uses} other{entries use}} the entry type.', ['num' => $num]),
            ),
        ]);
        $this->register('field', fn (FieldInterface $field) => [
            new FieldUsageBlocker($field),
        ]);
        $this->register('section', fn (Section $section) => [
            new TrashedElementsBlocker(
                Entry::class,
                ['sectionId' => $section->id],
                fn (int $num) => t('The section contains {num, number} {num, plural, =1{entry} other{entries}}.', ['num' => $num]),
                ['additionalActions' => $this->moveEntriesActions($section)],
            ),
            new SourceUsageBlocker(
                EntriesField::class,
                ["section:$section->uid"],
                $section->name,
                Table::ENTRIES,
                ['sectionId' => $section->id],
                fn (int $num) => t('The section is selected as a source for {num, number} {num, plural, =1{Entries field} other{Entries fields}}.', ['num' => $num]),
            ),
        ]);
        $this->register('site', fn (Site $site) => [
            new SiteSectionsBlocker($site),
        ]);
        $this->register('user-group', fn (UserGroup $group) => [
            new UserGroupUsageBlocker($group),
        ]);
        $this->register('volume', fn (Volume $volume) => [
            new TrashedElementsBlocker(
                Asset::class,
                ['volumeId' => $volume->id],
                fn (int $num) => t('The volume contains {num, number} {num, plural, =1{asset} other{assets}}.', ['num' => $num]),
            ),
            new SourceUsageBlocker(
                AssetsField::class,
                ["volume:$volume->uid"],
                (string) $volume->name,
                Table::ASSETS,
                ['volumeId' => $volume->id],
                fn (int $num) => t('The volume is selected as a source for {num, number} {num, plural, =1{Assets field} other{Assets fields}}.', ['num' => $num]),
            ),
            new UserPhotoVolumeBlocker($volume),
        ]);
    }

    /**
     * Registers a provider of blockers for a subject type, alongside any already
     * registered for it.
     *
     * The provider gets the subject to be deleted and returns the blockers to
     * add. {@see blockerData()} skips inactive blockers, so a provider can
     * return blockers that only apply to some subjects.
     *
     * @param  Closure(mixed): iterable<DeletionBlockerInterface>  $provider
     *
     * @throws InvalidArgumentException if the subject type isn’t registered
     */
    public function register(string $type, Closure $provider): void
    {
        if (! $this->subjectTypes->has($type)) {
            throw new InvalidArgumentException("Invalid deletion subject type: $type");
        }

        $this->providers[$type][] = $provider;
    }

    /**
     * Returns the blockers that should be addressed before a subject is deleted.
     *
     * Only active blockers apply; see {@see DeletionBlockerInterface::isActive()}.
     *
     * @return list<DeletionBlockerInterface>
     */
    public function blockers(string $type, mixed $subject): array
    {
        $blockers = [];

        foreach ($this->providers[$type] ?? [] as $provider) {
            foreach ($provider($subject) as $blocker) {
                if (! $blocker instanceof DeletionBlockerInterface) {
                    throw new InvalidArgumentException(sprintf('Deletion blocker providers for [%s] must return %s instances.', $type, DeletionBlockerInterface::class));
                }

                $blockers[] = $blocker;
            }
        }

        return $blockers;
    }

    /**
     * Prepares the active blockers for the control panel’s deletion blockers modal.
     *
     * A blocker’s details start out expanded when it has an `$expandDetails` property set to `true`.
     *
     * @param  DeletionBlockerInterface[]  $blockers
     * @return list<array{summary: string, details: string|null, actions: list<array<string, mixed>>, expanded: bool}>
     */
    public function blockerData(array $blockers): array
    {
        return collect($blockers)
            ->filter(fn (DeletionBlockerInterface $blocker) => $blocker->isActive())
            ->map(fn (DeletionBlockerInterface $blocker) => [
                'summary' => $blocker->getSummary(),
                'details' => $blocker->getDetails(),
                'actions' => $blocker->getActions(),
                'expanded' => property_exists($blocker, 'expandDetails') && $blocker->expandDetails,
            ])
            ->values()
            ->all();
    }

    /**
     * Returns a “Move entries” action for a section’s blocker, if another section accepts all of its entries’ types.
     *
     * @return list<array<string, mixed>>
     */
    private function moveEntriesActions(Section $section): array
    {
        $entryIds = Entry::find()
            ->sectionId($section->id)
            ->drafts(null)
            ->draftOf(false)
            ->whereNull('elements.canonicalId')
            ->site('*')
            ->unique()
            ->status(null)
            ->ids();

        if ($entryIds === []) {
            return [];
        }

        $typeIds = DB::table(Table::ENTRIES)
            ->whereIn('id', $entryIds)
            ->distinct()
            ->pluck('typeId')
            ->map(fn ($id) => (int) $id)
            ->all();
        $siteId = array_key_first($section->getSiteSettings());

        if ($siteId === null || Sections::getAvailableEntryMoveTargetSections($typeIds, $siteId, $section->uid) === []) {
            return [];
        }

        return [
            [
                'icon' => 'arrow-right',
                'label' => t('Move entries'),
                'callback' => Html::jsWithVars(fn ($entryIds, $siteId, $sectionUid) => <<<JS
new Craft.EntryMover($entryIds, null, {
  siteId: $siteId,
  currentSectionUid: $sectionUid,
  onMove: async () => {
    await blocker.manager.refresh();
    reject();
  },
  onCancel: () => reject(),
});
JS, [$entryIds, $siteId, $section->uid]),
            ],
        ];
    }
}
