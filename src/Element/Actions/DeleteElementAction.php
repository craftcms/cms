<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use craft\base\ElementInterface;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\Events\AfterDeleteElement;
use CraftCms\Cms\Element\Events\BeforeDeleteElement;
use CraftCms\Cms\Structure\Models\StructureElement as StructureElementModel;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\BulkOps;
use Illuminate\Support\Facades\DB;
use Throwable;

/** @internal */
readonly class DeleteElementAction
{
    public function __construct(
        private CascadeDeleteDraftsAndRevisionsAction $cascadeDeleteDraftsAndRevisions,
        private ElementCaches $elementCaches,
    ) {}

    public function handle(ElementInterface $element, bool $hardDelete = false): bool
    {
        event($event = new BeforeDeleteElement($element, $hardDelete));

        $element->hardDelete = $hardDelete || $event->hardDelete;

        if (! $element->beforeDelete()) {
            return false;
        }

        BulkOps::ensure(function () use ($element) {
            DB::beginTransaction();
            try {
                // First delete any structure nodes with this element, so NestedSetBehavior can do its thing.
                while (($record = StructureElementModel::where('elementId', $element->id)->first()) !== null) {
                    // If this element still has any children, move them up before the one getting deleted.
                    while (($child = $record->children(1)->first()) !== null) {
                        /** @var StructureElementModel $child */
                        $child->insertBefore($record);
                        // Re-fetch the record since its lft and rgt attributes just changed
                        $record->refresh();
                    }
                    // Delete this element’s node
                    $record->deleteWithChildren();
                }

                // Invalidate any caches involving this element
                $this->elementCaches->invalidateForElement($element);

                DateTimeHelper::pause();

                if ($element->hardDelete) {
                    DB::table(Table::ELEMENTS)->delete($element->id);
                    DB::table(Table::SEARCHINDEX)
                        ->where('elementId', $element->id)
                        ->delete();
                } else {
                    // Soft delete the elements table row
                    DB::table(Table::ELEMENTS)
                        ->where('id', $element->id)
                        ->update([
                            'dateUpdated' => $now = now(),
                            'dateDeleted' => $now,
                            'deletedWithOwner' => $element->deletedWithOwner,
                        ]);

                    // Also soft delete the element’s drafts & revisions
                    $this->cascadeDeleteDraftsAndRevisions->handle($element->id);
                }

                $element->dateDeleted = DateTimeHelper::now();
                $element->afterDelete();

                if (! $element->hardDelete) {
                    // Track this element in bulk operations
                    BulkOps::trackElement($element);
                }

                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            } finally {
                DateTimeHelper::resume();
            }
        });

        event(new AfterDeleteElement($element));

        return true;
    }
}
