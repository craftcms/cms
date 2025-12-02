<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry;

use Craft;
use craft\base\Element;
use craft\errors\InvalidElementException;
use craft\errors\UnsupportedSiteException;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Elements\Entry;
use CraftCms\Cms\Entry\Events\EntryMovedToSection;
use CraftCms\Cms\Entry\Events\MovingEntryToSection;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\DefaultPlacement;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Structure\Enums\Mode;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Structures;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use Exception;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Throwable;
use Tpetry\QueryExpressions\Language\Alias;

#[Singleton]
final class Entries
{
    /**
     * @var array<int,array<string,Entry|false>>
     */
    private array $singleEntries = [];

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
     * @return Entry|null The entry with the given ID, or `null` if an entry could not be found.
     */
    public function getEntryById(int $entryId, array|int|string|null $siteId = null, array $criteria = []): ?Entry
    {
        if (! $entryId) {
            return null;
        }

        // Get the structure ID
        if (! isset($criteria['structureId'])) {
            $criteria['structureId'] = DB::table(Table::ENTRIES, 'entries')
                ->join(new Alias(Table::SECTIONS, 'sections'), 'sections.id', 'entries.sectionId')
                ->where('entries.id', $entryId)
                ->value('sections.structureId');
        }

        return Craft::$app->getElements()->getElementById($entryId, Entry::class, $siteId, $criteria);
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

        if (! isset($this->singleEntries[$siteId])) {
            $this->singleEntries[$siteId] = [];
        }

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
        if (Event::hasListeners(MovingEntryToSection::class)) {
            Event::dispatch(new MovingEntryToSection($entry, $section));
        }

        // Make sure the element exists
        if (! $entry->id) {
            throw new Exception('Attempting to move an unsaved element.');
        }

        // and that it's not a nested entry
        if ($entry->getPrimaryOwnerId() !== null) {
            throw new Exception('Attempting to move a nested element.');
        }

        // Ensure all fields have been normalized
        $entry->getFieldValues();

        $oldSection = $entry->getSection();

        // move to new section
        $entry->sectionId = $section->id;

        // Validate
        $entry->setScenario(Element::SCENARIO_ESSENTIALS);
        $entry->validate();

        // If there are any errors on the URI, re-validate as disabled
        if ($entry->hasErrors('uri') && $entry->enabled) {
            $entry->enabled = false;
            $entry->validate();
        }

        // When moving to a section that allows for less authors than the entry has, allow the move.
        // The error will be shown the next time that entry is saved.
        if ($entry->hasErrors('authorIds')) {
            $entry->clearErrors('authorIds');
        }

        if ($entry->hasErrors()) {
            throw new InvalidElementException($entry,
                'Element '.$entry->id.' could not be moved because it doesn\'t validate.');
        }

        // prevents revision from being created
        $entry->resaving = true;

        $elementsService = Craft::$app->getElements();
        $elementsService->ensureBulkOp(function () use (
            $entry,
            $section,
            $oldSection,
            $elementsService,
        ) {
            DB::beginTransaction();
            try {
                // Start with $entry’s site
                if (! $elementsService->saveElement($entry, false, false)) {
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
                        $draftsQuery->each(function (Entry $draft) use ($oldSection) {
                            if ($draft->lft) {
                                Structures::remove($oldSection->structureId, $draft);
                            }
                        }, 100);

                        $revisionsQuery->each(function (Entry $revision) use ($oldSection) {
                            if ($revision->lft) {
                                Structures::remove($oldSection->structureId, $revision);
                            }
                        }, 100);
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
                \yii\caching\TagDependency::invalidate(resolve('Craft')->getCache(), $tag);
            } catch (Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        });

        if (Event::hasListeners(EntryMovedToSection::class)) {
            Event::dispatch(new EntryMovedToSection($entry, $section));
        }

        return true;
    }
}
