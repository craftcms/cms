<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use craft\base\ElementInterface;
use craft\db\Command;
use craft\fieldlayoutelements\CustomField;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Str;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;
use yii\base\Component;

/**
 * Relations service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getRelations()|`Craft::$app->getRelations()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 5.3.0
 */
class Relations extends Component
{
    /**
     * Saves some relations for a field.
     *
     * @param BaseRelationField $field
     * @param ElementInterface $source
     * @param array $targetIds
     *
     * @throws Throwable
     */
    public function saveRelations(BaseRelationField $field, ElementInterface $source, array $targetIds): void
    {
        // Get the unique, indexed target IDs, set to their 0-indexed sort orders
        $targetIds = array_flip(array_values(array_unique(array_filter($targetIds))));

        // Get the current relations
        $oldRelations = DB::table(Table::RELATIONS)
            ->select(['id', 'sourceSiteId', 'targetId', 'sortOrder'])
            ->where('fieldId', $field->id)
            ->where('sourceId', $source->id)
            ->when(
                $field->localizeRelations,
                fn(Builder $query) => $query->where(function(Builder $query) use ($source) {
                    $query->whereNull('sourceSiteId')
                        ->orWhere('sourceSiteId', $source->siteId);
                }),
            )
            ->get();

        /** @var Command[] $updateCommands */
        $updateCommands = [];
        $deleteIds = [];

        $sourceSiteId = $field->localizeRelations ? $source->siteId : null;

        foreach ($oldRelations as $relation) {
            // Does this relation still exist?
            if (isset($targetIds[$relation->targetId])) {
                // Anything to update?
                $sortOrder = $targetIds[$relation->targetId] + 1;
                // only update relations if the source is not being propagated
                // https://github.com/craftcms/cms/issues/12702
                if ((!$source->propagating && $relation->sourceSiteId != $sourceSiteId) || $relation->sortOrder != $sortOrder) {
                    $updateCommands[] = [
                        'id' => $relation->id,
                        'sourceSiteId' => $sourceSiteId,
                        'sortOrder' => $sortOrder,
                    ];
                }

                // Avoid re-inserting it
                unset($targetIds[$relation->targetId]);
            } else {
                $deleteIds[] = $relation->id;
            }
        }

        if (!empty($updateCommands) || !empty($deleteIds) || !empty($targetIds)) {
            DB::beginTransaction();
            try {
                foreach ($updateCommands as $command) {
                    DB::table(Table::RELATIONS)
                        ->where('id', Arr::pull($command, 'id'))
                        ->update(array_merge([
                            'dateUpdated' => now(),
                        ], $command));
                }

                $now = now();
                // Add the new ones
                if (!empty($targetIds)) {
                    $values = [];
                    foreach ($targetIds as $targetId => $sortOrder) {
                        $values[] = [
                            'fieldId' => $field->id,
                            'sourceId' => $source->id,
                            'sourceSiteId' => $sourceSiteId,
                            'targetId' => $targetId,
                            'sortOrder' => $sortOrder + 1,
                            'dateCreated' => $now,
                            'dateUpdated' => $now,
                            'uid' => Str::uuid(),
                        ];
                    }

                    DB::table(Table::RELATIONS)->insert($values);
                }

                if (!empty($deleteIds)) {
                    DB::table(Table::RELATIONS)
                        ->whereIn('id', $deleteIds)
                        ->delete();
                }

                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        }
    }

    /**
     * Deletes relations that don’t belong to a relational field on the given element’s field layout.
     *
     * @param ElementInterface $element
     *
     * @since 4.8.0
     */
    public function deleteLeftoverRelations(ElementInterface $element): void
    {
        if (!$element->id) {
            return;
        }

        $fieldLayout = $element->getFieldLayout();
        if (!$fieldLayout) {
            return;
        }

        $relationFieldIds = [];
        foreach ($fieldLayout->getTabs() as $tab) {
            foreach ($tab->getElements() as $layoutElement) {
                if ($layoutElement instanceof CustomField) {
                    $field = $layoutElement->getField();
                    if ($field instanceof BaseRelationField) {
                        $relationFieldIds[] = $field->id;
                    }
                }
            }
        }

        // get those relations for the element that don't belong to any relational fields that are in the layout
        $leftoverRelationIds = DB::table(Table::RELATIONS)
            ->where('sourceId', $element->id)
            ->when(
                !empty($relationFieldIds),
                fn(Builder $query) => $query->whereNotIn('fieldId', $relationFieldIds),
            )
            ->pluck('id');

        if ($leftoverRelationIds->isEmpty()) {
            return;
        }

        // if relations were returned - delete them
        DB::table(Table::RELATIONS)
            ->whereIn('id', $leftoverRelationIds)
            ->delete();
    }
}
