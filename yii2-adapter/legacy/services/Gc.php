<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\ElementInterface;
use craft\errors\FsException;
use CraftCms\Cms\GarbageCollection\Actions\DeleteOrphanedFieldLayouts;
use CraftCms\Cms\GarbageCollection\Actions\DeleteOrphanedNestedElements;
use CraftCms\Cms\GarbageCollection\Actions\DeletePartialElements;
use CraftCms\Cms\GarbageCollection\Actions\HardDelete;
use CraftCms\Cms\GarbageCollection\Actions\HardDeleteElements;
use CraftCms\Cms\GarbageCollection\Actions\HardDeleteVolumes;
use CraftCms\Cms\GarbageCollection\Actions\RemoveEmptyTempFolders;
use CraftCms\Cms\GarbageCollection\Events\RunningGarbageCollection;
use CraftCms\Cms\GarbageCollection\GarbageCollection;
use Illuminate\Support\Facades\Event;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Garbage Collection service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getGc()|`Craft::$app->getGc()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.0
 * @deprecated 6.0.0 use {@see GarbageCollection} instead.
 */
class Gc extends Component
{
    /**
     * @event Event The event that is triggered when running garbage collection.
     */
    public const EVENT_RUN = 'run';

    /**
     * @var int the probability (parts per million) that garbage collection (GC) should be performed
     * on a request. Defaults to 10, meaning 0.001% chance.
     *
     * This number should be between 0 and 1000000. A value 0 means no GC will be performed at all unless forced.
     */
    public int $probability {
        /** @phpstan-ignore-next-line */
        get => app(GarbageCollection::class)->probability;
        set(int $value) => app(GarbageCollection::class)->probability = $value;
    }

    /**
     * @var bool whether [[hardDelete()]] should delete *all* soft-deleted rows,
     * rather than just the ones that were deleted long enough ago to be ready
     * for hard-deletion per the <config5:softDeleteDuration> config setting.
     */
    public bool $deleteAllTrashed {
        /** @phpstan-ignore-next-line */
        get => app(GarbageCollection::class)->deleteAllTrashed;
        set(bool $value) => app(GarbageCollection::class)->deleteAllTrashed = $value;
    }

    /**
     * @var bool Whether CLI output should be muted.
     * @since 5.4.9
     */
    public bool $silent {
        /** @phpstan-ignore-next-line */
        get => app(GarbageCollection::class)->silent;
        set(bool $value) => app(GarbageCollection::class)->silent = $value;
    }

    /**
     * Possibly runs garbage collection.
     *
     * @param bool $force Whether garbage collection should be forced. If left as `false`, then
     * garbage collection will only run if a random condition passes, factoring in [[probability]].
     */
    public function run(bool $force = false): void
    {
        app(GarbageCollection::class)->run($force);
    }

    /**
     * Hard delete eligible volumes, deleting the folders one by one to avoid nested dependency errors.
     * @deprecated 6.0.0 use {@see HardDeleteVolumes} instead.
     */
    public function hardDeleteVolumes(): void
    {
        app(HardDeleteVolumes::class)->__invoke();
    }

    /**
     * Hard-deletes eligible elements.
     *
     * Any soft-deleted nested elements which have revisions will be skipped, as their revisions may still be needed by the owner element.
     *
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see HardDeleteElements} instead.
     */
    public function hardDeleteElements(): void
    {
        app(HardDeleteElements::class)->__invoke();
    }

    /**
     * Hard-deletes any rows in the given table(s), that are due for it.
     *
     * @param string|string[] $tables The table(s) to delete rows from. They must have a `dateDeleted` column.
     * @deprecated 6.0.0 use {@see HardDelete} instead.
     */
    public function hardDelete(array|string $tables): void
    {
        app(HardDelete::class, ['tables' => $tables])->__invoke();
    }

    /**
     * Deletes elements that are missing data in the given element extension table.
     *
     * @param class-string<ElementInterface> $elementType The element type
     * @param string $table The extension table name
     * @param string $fk The column name that contains the foreign key to `elements.id`
     *
     * @since 3.6.6
     * @deprecated 6.0.0 use {@see DeletePartialElements} instead.
     */
    public function deletePartialElements(string $elementType, string $table, string $fk): void
    {
        app(DeletePartialElements::class, [
            'elementType' => $elementType,
            'table' => $table,
            'foreignKey' => $fk,
        ])->__invoke();
    }

    /**
     * Find all temp upload folders with no assets in them and remove them.
     *
     * @throws FsException
     * @throws Exception
     * @throws InvalidConfigException
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see RemoveEmptyTempFolders} instead.
     */
    public function removeEmptyTempFolders(): void
    {
        app(RemoveEmptyTempFolders::class)->__invoke();
    }

    /**
     * Deletes elements which have a `fieldId` value, but it’s set to an invalid field ID,
     * or they're missing a row in the `elements_owners` table.
     *
     * @param class-string<ElementInterface> $elementType The element type
     * @param string $table The extension table name
     * @param string $fieldFk The column name that contains the foreign key to `fields.id`
     *
     * @since 5.4.2
     * @deprecated 6.0.0 use {@see DeleteOrphanedNestedElements} instead.
     */
    public function deleteOrphanedNestedElements(string $elementType, string $table, string $fieldFk = 'fieldId'): void
    {
        app(DeleteOrphanedNestedElements::class, [
            'elementType' => $elementType,
            'table' => $table,
            'fieldForeignKey' => $fieldFk,
        ])->__invoke();
    }

    /**
     * Deletes field layouts that are no longer used.
     *
     * @param class-string<ElementInterface> $elementType The element type
     * @param string $table The  table name that contains a foreign key to `fieldlayouts.id`
     * @param string $fk The column name that contains the foreign key to `fieldlayouts.id`
     *
     * @since 5.5.0
     * @deprecated 6.0.0 use {@see DeleteOrphanedFieldLayouts} instead.
     */
    public function deleteOrphanedFieldLayouts(string $elementType, string $table, string $fk = 'fieldLayoutId'): void
    {
        app(DeleteOrphanedFieldLayouts::class, [
            'elementType' => $elementType,
            'table' => $table,
            'foreignKey' => $fk,
        ])->__invoke();
    }

    public static function registerEvents(): void
    {
        Event::listen(RunningGarbageCollection::class, function() {
            if (Craft::$app->getGc()->hasEventHandlers(self::EVENT_RUN)) {
                Craft::$app->getGc()->trigger(self::EVENT_RUN);
            }
        });
    }
}
