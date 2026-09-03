<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Exceptions\UnsupportedSiteException;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Events\EntryMovedToSection;
use CraftCms\Cms\Entry\Events\EntryMovingToSection;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\DefaultPlacement;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Structure\Enums\Mode;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\BulkOps;
use CraftCms\Cms\Support\Facades\ElementCaches;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Structures;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use Exception;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;
use Tpetry\QueryExpressions\Language\Alias;

#[Scoped]
class Entries
{
    /**
     * @var array<int,array<string,Entry|false>>
     */
    private array $singleEntries = [];

    public function __construct(
        private readonly Elements $elements,
    ) {}

    /**
     * Returns an entry by its ID.
     *
     * ```php
     * $entry = \Craft::$app->entries->getEntryById($entryId);
     * ```
     *
     * @param  int  $entryId  The entry’s ID.
     * @param  int|string|int[]|null  $siteId  The site(s) to fetch the entry in.
     *                                         Defaults to the current site.
     * @param  array<string, array<array-key, int|string>|bool|int|string|null>  $criteria
     * @return Entry|null The entry with the given ID, or `null` if an entry could not be found.
     */
    public function getEntryById(int $entryId, array|int|string|null $siteId = null, array $criteria = []): ?Entry
    {
        if (! $entryId) {
            return null;
        }

        // Get the structure ID
        $criteria['structureId'] ??= DB::table(Table::ENTRIES, 'entries')
            ->join(new Alias(Table::SECTIONS, 'sections'), 'sections.id', 'entries.sectionId')
            ->where('entries.id', $entryId)
            ->value('sections.structureId');

        return $this->elements->getElementById($entryId, Entry::class, $siteId, $criteria);
    }

    /**
     * Returns an array of Single section entries which match a given list of section handles.
     *
     * @param  string[]  $handles
     * @return array<string,Entry>
     */
    public function getSingleEntriesByHandle(array $handles): array
    {
        $entries = [];
        $siteId = Sites::getCurrentSite()->id;
        $missingEntries = [];

        $this->singleEntries[$siteId] ??= [];

        foreach ($handles as $handle) {
            if (isset($this->singleEntries[$siteId][$handle])) {
                if ($this->singleEntries[$siteId][$handle] !== false) {
                    $entries[$handle] = $this->singleEntries[$siteId][$handle];
                }
            } else {
                $missingEntries[] = $handle;
            }
        }

        if (empty($missingEntries)) {
            return $entries;
        }

        /** @var array<string,Section> $singleSections */
        $singleSections = Sections::getSectionsByType(SectionType::Single)->keyBy('handle');
        $fetchSectionIds = [];
        $fetchSectionHandles = [];
        foreach ($missingEntries as $handle) {
            if (isset($singleSections[$handle])) {
                $fetchSectionIds[] = $singleSections[$handle]->id;
                $fetchSectionHandles[] = $handle;
            } else {
                $this->singleEntries[$siteId][$handle] = false;
            }
        }

        if (! empty($fetchSectionIds)) {
            $fetchedEntries = Entry::find()
                ->sectionId($fetchSectionIds)
                ->siteId($siteId)
                ->all();

            /** @var array<string,Entry> $fetchedEntries */
            $fetchedEntries = Arr::keyBy($fetchedEntries, fn (Entry $entry) => $entry->getSection()->handle);

            foreach ($fetchSectionHandles as $handle) {
                if (isset($fetchedEntries[$handle])) {
                    $this->singleEntries[$siteId][$handle] = $fetchedEntries[$handle];
                    $entries[$handle] = $fetchedEntries[$handle];
                } else {
                    $this->singleEntries[$siteId][$handle] = false;
                }
            }
        }

        return $entries;
    }

    public function refreshSingleEntries(): void
    {
        $this->singleEntries = [];
    }

    /**
     * Move entry to a different section.
     *
     *
     * @throws Exception
     * @throws InvalidElementException
     * @throws Throwable
     * @throws UnsupportedSiteException
     */
    public function moveEntryToSection(Entry $entry, Section $section): bool
    {
        // todo: what about revisions or drafts that might be of a type that's not compatible with the new section?
        event(new EntryMovingToSection($entry, $section));

        // Make sure the element exists
        if (! $entry->id) {
            throw new Exception('Attempting to move an unsaved element.');
        }

        // and that it's not a nested entry
        if ($entry->getPrimaryOwnerId() !== null) {
            throw new Exception('Attempting to move a nested element.');
        }

        $sectionEntryTypeIds = array_map(fn ($entryType) => $entryType->id, $section->getEntryTypes());

        if (! in_array($entry->typeId, $sectionEntryTypeIds, true)) {
            throw new Exception('Entry type is not supported by the target section.');
        }

        // Ensure all fields have been normalized
        $entry->getFieldValues();

        $oldSection = $entry->getSection();

        // move to new section
        $entry->sectionId = $section->id;

        // Validate
        $entry->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);
        $entry->validate();

        // If there are any errors on the URI, re-validate as disabled
        if ($entry->errors()->has('uri') && $entry->enabled) {
            $entry->enabled = false;
            $entry->validate();
        }

        // When moving to a section that allows for less authors than the entry has, allow the move.
        // The error will be shown the next time that entry is saved.
        if ($entry->errors()->has('authorIds')) {
            $entry->errors()->forget('authorIds');
        }

        if ($entry->errors()->isNotEmpty()) {
            throw new InvalidElementException($entry,
                'Element '.$entry->id.' could not be moved because it doesn\'t validate.');
        }

        // prevents revision from being created
        $entry->resaving = true;

        BulkOps::ensure(function () use (
            $entry,
            $section,
            $oldSection,
        ) {
            DB::beginTransaction();
            try {
                // Start with $entry’s site
                if (! $this->elements->saveElement($entry, false, false)) {
                    throw new InvalidElementException($entry,
                        'Element '.$entry->id.' could not be moved for site '.$entry->siteId);
                }

                $draftsQuery = Entry::find()
                    ->draftOf($entry)
                    ->provisionalDrafts(null)
                    ->status(null)
                    ->site('*')
                    ->unique();

                $revisionsQuery = Entry::find()
                    ->revisionOf($entry)
                    ->status(null)
                    ->site('*')
                    ->unique();

                if (
                    $entry->getIsCanonical() &&
                    in_array(SectionType::Structure, [$oldSection->type, $section->type])
                ) {
                    // if we're moving it from a Structure section, remove it from the structure
                    if ($oldSection->type === SectionType::Structure) {
                        Structures::remove($oldSection->structureId, $entry);

                        // remove drafts and revisions from the structure, too
                        $draftsQuery->cursor()->each(function (Entry $draft) use ($oldSection) {
                            if ($draft->lft) {
                                Structures::remove($oldSection->structureId, $draft);
                            }
                        });

                        $revisionsQuery->cursor()->each(function (Entry $revision) use ($oldSection) {
                            if ($revision->lft) {
                                Structures::remove($oldSection->structureId, $revision);
                            }
                        });
                    }

                    // if we're moving it to a Structure section, place it at the root
                    if ($section->type === SectionType::Structure) {
                        if ($section->defaultPlacement === DefaultPlacement::Beginning) {
                            Structures::prependToRoot($section->structureId, $entry, Mode::Insert);
                        } else {
                            Structures::appendToRoot($section->structureId, $entry, Mode::Insert);
                        }
                    }
                }

                $entry->newSiteIds = [];
                $entry->afterPropagate(false);

                // now assign drafts & revisions to the new section too
                $ids = array_merge($draftsQuery->ids(), $revisionsQuery->ids());
                if (! empty($ids)) {
                    DB::table(Table::ENTRIES)
                        ->whereIn('id', $ids)
                        ->update([
                            'sectionId' => $section->id,
                            'dateUpdated' => now(),
                        ]);
                }

                DB::commit();

                // Invalidate caches for the old section
                $tag = sprintf('element::%s::section:%s', Entry::class, $oldSection->id);
                TagDependency::invalidate($tag);
            } catch (Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        });

        event(new EntryMovedToSection($entry, $section));

        return true;
    }

    /**
     * Reassigns entries to a new author.
     *
     * @param  int|int[]  $oldUserId
     * @return int The number of affected entries
     */
    public function reassignEntries(int|array $oldUserId, int $newUserId): int
    {
        $oldUserIds = Arr::wrap($oldUserId);

        if (in_array($newUserId, $oldUserIds, true)) {
            throw new InvalidArgumentException('The new author must be different from the old author.');
        }

        $count = DB::table(Table::ENTRIES_AUTHORS)
            ->whereIn('authorId', $oldUserIds)
            ->whereNotIn('entryId', function (Builder $query) use ($newUserId) {
                // Wrap the subquery in an extra derived table so MySQL doesn't
                // treat it as selecting from the table being updated.
                $query->fromSub(function (Builder $query) use ($newUserId) {
                    $query->select('entryId')
                        ->from(Table::ENTRIES_AUTHORS, 'ea2')
                        ->where('authorId', $newUserId);
                }, 'ea2');
            })
            ->update([
                'authorId' => $newUserId,
            ]);

        ElementCaches::invalidateForElementType(Entry::class);

        return $count;
    }
}
