<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection;

use Craft;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Elements\ContentBlock;
use CraftCms\Cms\GarbageCollection\Actions\DeleteOrphanedDraftsAndRevisions;
use CraftCms\Cms\GarbageCollection\Actions\DeleteOrphanedFieldLayouts;
use CraftCms\Cms\GarbageCollection\Actions\DeleteOrphanedForeignKeyRows;
use CraftCms\Cms\GarbageCollection\Actions\DeleteOrphanedNestedElements;
use CraftCms\Cms\GarbageCollection\Actions\DeleteOrphanedRelations;
use CraftCms\Cms\GarbageCollection\Actions\DeleteOrphanedSearchIndexes;
use CraftCms\Cms\GarbageCollection\Actions\DeleteOrphanedSearchIndexJobs;
use CraftCms\Cms\GarbageCollection\Actions\DeleteOrphanedStructureElements;
use CraftCms\Cms\GarbageCollection\Actions\DeletePartialElements;
use CraftCms\Cms\GarbageCollection\Actions\DeletePointlessChangeData;
use CraftCms\Cms\GarbageCollection\Actions\DeleteStaleAnnouncements;
use CraftCms\Cms\GarbageCollection\Actions\DeleteStaleBulkOpData;
use CraftCms\Cms\GarbageCollection\Actions\DeleteStaleElementActivity;
use CraftCms\Cms\GarbageCollection\Actions\DeleteUnsupportedSiteEntries;
use CraftCms\Cms\GarbageCollection\Actions\FireRunEvent;
use CraftCms\Cms\GarbageCollection\Actions\GarbageCollectionAction;
use CraftCms\Cms\GarbageCollection\Actions\HardDelete;
use CraftCms\Cms\GarbageCollection\Actions\HardDeleteElements;
use CraftCms\Cms\GarbageCollection\Actions\HardDeleteStructures;
use CraftCms\Cms\GarbageCollection\Actions\HardDeleteVolumes;
use CraftCms\Cms\GarbageCollection\Actions\PurgePendingUsers;
use CraftCms\Cms\GarbageCollection\Actions\PurgeUnsavedDrafts;
use CraftCms\Cms\GarbageCollection\Actions\RemoveEmptyTempFolders;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Lottery;

final class GarbageCollection
{
    /**
     * @var int The number of items that should be deleted in a single batch.
     */
    public const int CHUNK_SIZE = 10_000;

    /**
     * @var int the probability (parts per million) that garbage collection (GC) should be performed
     *          on a request. Defaults to 10, meaning 0.001% chance.
     *
     * This number should be between 0 and 1000000. A value 0 means no GC will be performed at all unless forced.
     */
    public int $probability = 10;

    /**
     * @var bool whether [[hardDelete()]] should delete *all* soft-deleted rows,
     *           rather than just the ones that were deleted long enough ago to be ready
     *           for hard-deletion per the <config5:softDeleteDuration> config setting.
     */
    public bool $deleteAllTrashed = false;

    /**
     * @var bool Whether CLI output should be muted.
     */
    public bool $silent = false;

    /**
     * Possibly runs garbage collection.
     *
     * @param  bool  $force  Whether garbage collection should be forced. If left as `false`, then
     *                       garbage collection will only run if a random condition passes, factoring in [[probability]].
     */
    public function run(bool $force = false): void
    {
        if (! $force && ! Lottery::odds($this->probability, 1_000_000)->choose()) {
            return;
        }

        $this->runActions([
            PurgeUnsavedDrafts::class,
            PurgePendingUsers::class,
            DeleteStaleAnnouncements::class,
            DeleteStaleElementActivity::class,
            DeleteStaleBulkOpData::class,

            // elements should always go first
            HardDeleteElements::class,

            [HardDelete::class, [
                'tables' => [
                    Table::ENTRYTYPES,
                    Table::FIELDS,
                    Table::SECTIONS,
                ],
            ]],
            [DeletePartialElements::class, ['elementType' => Address::class, 'table' => Table::ADDRESSES]],
            [DeletePartialElements::class, ['elementType' => Asset::class, 'table' => Table::ASSETS]],
            [DeletePartialElements::class, ['elementType' => ContentBlock::class, 'table' => Table::CONTENTBLOCKS]],
            [DeletePartialElements::class, ['elementType' => Entry::class, 'table' => Table::ENTRIES]],
            [DeletePartialElements::class, ['elementType' => User::class, 'table' => Table::USERS]],
            [DeleteOrphanedFieldLayouts::class, ['elementType' => Asset::class, 'table' => Table::VOLUMES]],
            [DeleteOrphanedFieldLayouts::class, ['elementType' => Entry::class, 'table' => Table::ENTRYTYPES]],
            DeleteUnsupportedSiteEntries::class,
            [DeleteOrphanedNestedElements::class, ['elementType' => Address::class, 'table' => Table::ADDRESSES]],
            [DeleteOrphanedNestedElements::class, ['elementType' => ContentBlock::class, 'table' => Table::CONTENTBLOCKS]],
            [DeleteOrphanedNestedElements::class, ['elementType' => Entry::class, 'table' => Table::ENTRIES]],

            // Fire a 'run' event
            // Note this should get fired *before* orphaned drafts & revisions are deleted
            // (see https://github.com/craftcms/cms/issues/14309)
            FireRunEvent::class,

            DeleteOrphanedDraftsAndRevisions::class,
            DeleteOrphanedSearchIndexes::class,
            DeleteOrphanedRelations::class,
            DeleteOrphanedSearchIndexJobs::class,
            DeleteOrphanedStructureElements::class,
            DeleteOrphanedForeignKeyRows::class,
            DeletePointlessChangeData::class,
            HardDeleteStructures::class,
            [HardDelete::class, ['tables' => [
                Table::FIELDLAYOUTS,
                Table::SITES,
            ]]],
            HardDeleteVolumes::class,
            RemoveEmptyTempFolders::class,
        ]);

        // Invalidate all element caches so any hard-deleted elements don't look like they still exist
        Craft::$app->getElements()->invalidateAllCaches();
    }

    /**
     * @param array<array{
     *     0: class-string<GarbageCollectionAction>,
     *     1: array
     * }|class-string<GarbageCollectionAction>> $actions
     */
    public function runActions(array $actions): void
    {
        foreach ($actions as $action) {
            if (is_array($action)) {
                [$action, $params] = $action;
            }

            app($action, array_merge([
                'garbageCollection' => $this,
            ], $params ?? []))();
        }
    }
}
