<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use craft\base\ElementInterface;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Contracts\RelationalFieldInterface;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Str;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

#[Singleton]
readonly class ElementRelations
{
    /**
     * Updates relation data for the given element after save.
     *
     * This handles insert/update/delete of relations based on the element's
     * relational field values.
     */
    public function updateRelations(ElementInterface $element, bool $isNew): void
    {
        if (! $this->hasFieldLayout($element)) {
            return;
        }

        $fields = $this->relationalFields($element);

        /** @var int[] $skipFieldIds */
        $skipFieldIds = [];
        /** @var array<int,int|null> $sourceSiteIds */
        $sourceSiteIds = [];
        /** @var array<int,array<int,int>> $relationData */
        $relationData = [];

        foreach ($fields as $fieldId => $instances) {
            $localizeRelations = $instances[0]->localizeRelations();
            $include = false;

            foreach ($instances as $field) {
                // Skip if nothing changed, or the element is just propagating and we're not localizing relations
                if (
                    $isNew || (
                        ($element->duplicateOf || $element->isFieldDirty($field->handle) || $field->forceUpdateRelations($element)) &&
                        (! $element->propagating || $localizeRelations)
                    )
                ) {
                    $include = true;
                    break;
                }
            }

            if ($include) {
                // Create target ID => sort order mappings for the field
                foreach ($instances as $field) {
                    $sourceSiteIds[$field->id] = $localizeRelations ? $element->siteId : null;
                    $relationData[$field->id] ??= [];
                    foreach ($field->getRelationTargetIds($element) as $targetId) {
                        if (! isset($relationData[$field->id][$targetId])) {
                            $relationData[$field->id][$targetId] = count($relationData[$field->id]) + 1;
                        }
                    }
                }
            } else {
                $skipFieldIds[] = $fieldId;
            }
        }

        // Get the old relations
        $oldRelations = DB::table(Table::RELATIONS)
            ->where('sourceId', $element->id)
            ->where(function (Builder $query) use ($element) {
                $query->whereNull('sourceSiteId')
                    ->orWhere('sourceSiteId', $element->siteId);
            })
            // Exclude the skipped fields rather than listing included fields,
            // so we also get any relations for fields that aren't part of the layout
            // (https://github.com/craftcms/cms/issues/13956)
            ->unless(empty($skipFieldIds), fn (Builder $query) => $query->whereNotIn('fieldId', $skipFieldIds))
            ->select(['id', 'fieldId', 'sourceSiteId', 'targetId', 'sortOrder'])
            ->get()
            ->map(fn (object $data) => (array) $data);

        $updateCommands = [];
        $deleteIds = [];

        foreach ($oldRelations as $relation) {
            [$relationId, $fieldId, $oldSourceSiteId, $targetId, $oldSortOrder] = [
                $relation['id'],
                $relation['fieldId'],
                $relation['sourceSiteId'],
                $relation['targetId'],
                $relation['sortOrder'],
            ];

            // Does this relation still exist?
            if (isset($relationData[$fieldId][$targetId])) {
                // Anything to update?
                if ($oldSourceSiteId != $sourceSiteIds[$fieldId] || $oldSortOrder != $relationData[$fieldId][$targetId]) {
                    $updateCommands[] = [
                        'id' => $relationId,
                        'sourceSiteId' => $sourceSiteIds[$fieldId],
                        'sortOrder' => $relationData[$fieldId][$targetId],
                    ];
                }

                // Avoid re-inserting it
                unset($relationData[$fieldId][$targetId]);
                if (empty($relationData[$fieldId])) {
                    unset($relationData[$fieldId]);
                }
            } else {
                $deleteIds[] = $relationId;
            }
        }

        if (empty($updateCommands) && empty($deleteIds) && empty($relationData)) {
            // Nothing to do here
            return;
        }

        DB::beginTransaction();

        foreach ($updateCommands as $command) {
            DB::table(Table::RELATIONS)
                ->where('id', Arr::pull($command, 'id'))
                ->update($command);
        }

        // Add the new ones
        if (! empty($relationData)) {
            $now = now();
            $values = [];
            foreach ($relationData as $fieldId => $targetIds) {
                foreach ($targetIds as $targetId => $sortOrder) {
                    $values[] = [
                        'fieldId' => $fieldId,
                        'sourceId' => $element->id,
                        'sourceSiteId' => $sourceSiteIds[$fieldId],
                        'targetId' => $targetId,
                        'sortOrder' => $sortOrder,
                        'dateCreated' => $now,
                        'dateUpdated' => $now,
                        'uid' => Str::uuid(),
                    ];
                }
            }

            DB::table(Table::RELATIONS)
                ->insert($values);
        }

        if (! empty($deleteIds)) {
            DB::table(Table::RELATIONS)
                ->whereIn('id', $deleteIds)
                ->delete();
        }

        DB::commit();
    }

    /**
     * Deletes site-specific relation data for the given element.
     *
     * This is called when an element is deleted for a specific site.
     */
    public function deleteSiteRelations(ElementInterface $element): void
    {
        if ($this->hasFieldLayout($element)) {
            DB::table(Table::RELATIONS)
                ->where('sourceId', $element->id)
                ->where('sourceSiteId', $element->siteId)
                ->delete();
        }
    }

    /**
     * Returns all relational fields for the given element, grouped by field ID.
     *
     * @return array<int,RelationalFieldInterface[]>
     */
    public function relationalFields(ElementInterface $element): array
    {
        $fields = [];
        foreach ($this->getFieldLayoutFields($element) as $field) {
            if ($field instanceof RelationalFieldInterface) {
                $fields[$field->id][] = $field;
            }
        }

        return $fields;
    }

    /**
     * Checks whether the element has a field layout with tabs.
     */
    private function hasFieldLayout(ElementInterface $element): bool
    {
        $fieldLayout = $element->getFieldLayout();

        return $fieldLayout && ! empty($fieldLayout->getTabs());
    }

    /**
     * Returns the custom fields for the given element's field layout.
     *
     * @return FieldInterface[]
     */
    private function getFieldLayoutFields(ElementInterface $element): array
    {
        $fieldLayout = $element->getFieldLayout();

        return $fieldLayout?->getCustomFields() ?? [];
    }
}
