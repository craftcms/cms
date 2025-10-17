<?php

namespace CraftCms\Cms\GarbageCollection\Actions;

use craft\base\NestedElementInterface;
use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Function\Conditional\Coalesce;
use Tpetry\QueryExpressions\Language\Alias;

/**
 * Hard-deletes eligible elements.
 *
 * Any soft-deleted nested elements that have revisions will be skipped, as their revisions may still be needed by the owner element.
 */
final class HardDeleteElements extends GarbageCollectionAction
{
    public function __invoke(): void
    {
        if (! $this->shouldHardDelete()) {
            return;
        }

        $normalElementTypes = [];
        $nestedElementTypes = [];

        foreach (\Craft::$app->getElements()->getAllElementTypes() as $elementType) {
            if (is_subclass_of($elementType, NestedElementInterface::class)) {
                $nestedElementTypes[] = $elementType;
            } else {
                $normalElementTypes[] = $elementType;
            }
        }

        $this->components->task(
            'deleting trashed elements',
            function () use ($normalElementTypes, $nestedElementTypes) {
                $this->deleteTrashedElements($normalElementTypes, $nestedElementTypes);
            },
        );
    }

    private function deleteTrashedElements(array $normalElementTypes, array $nestedElementTypes): void
    {
        if ($normalElementTypes) {
            $this->hardDeleteQuery(DB::table(Table::ELEMENTS))
                ->whereIn('type', $normalElementTypes)
                ->delete();
        }

        if (empty($nestedElementTypes)) {
            return;
        }

        // first get nested elements which are not nested (owned) and that don't have any revisions
        $ids1 = $this->hardDeleteQuery(DB::table(Table::ELEMENTS, 'e'), 'e')
            ->leftJoin(new Alias(Table::REVISIONS, 'r'), 'r.canonicalId', 'e.id')
            ->leftJoin(new Alias(Table::ELEMENTS_OWNERS, 'eo'), 'eo.elementId', '=', new Coalesce(['e.canonicalId', 'e.id']))
            ->whereIn('e.type', $nestedElementTypes)
            ->whereNull('r.id')
            ->whereNull('eo.elementId')
            ->pluck('e.id');

        // then get any nested elements that don't have any revisions, including nested ones
        $ids2 = $this->hardDeleteQuery(DB::table(Table::ELEMENTS, 'e'), 'e')
            ->leftJoin(new Alias(Table::REVISIONS, 'r'), 'r.canonicalId', '=', new Coalesce(['e.canonicalId', 'e.id']))
            ->whereIn('type', $nestedElementTypes)
            ->whereNull('r.id')
            ->pluck('e.id');

        $ids1
            ->merge($ids2)
            ->unique()
            ->chunk($this->garbageCollection::CHUNK_SIZE)
            ->each(function ($idsChunk) {
                DB::table(Table::ELEMENTS)
                    ->whereIn('id', $idsChunk)
                    ->delete();
            });
    }
}
