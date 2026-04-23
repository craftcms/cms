<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\GarbageCollection\GarbageCollection;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\Alias;

/**
 * Deletes elements which have a `fieldId` value, but it’s set to an invalid field ID,
 * or they're missing a row in the `elements_owners` table.
 */
class DeleteOrphanedNestedElements extends GarbageCollectionAction
{
    public function __construct(
        GarbageCollection $garbageCollection,
        GeneralConfig $generalConfig,
        /** @var class-string<ElementInterface> */
        private readonly string $elementType,
        private readonly string $table,
        private readonly string $fieldForeignKey = 'fieldId',
    ) {
        parent::__construct($garbageCollection, $generalConfig);
    }

    public function __invoke(): void
    {
        $this->components->task(
            sprintf('deleting orphaned nested %s', $this->elementType::pluralLowerDisplayName()),
            function () {
                // IDs of nested elements where the owner no longer exists
                $ids1 = DB::table(Table::ELEMENTS, 'el')
                    ->join(new Alias($this->table, 't'), 't.id', 'el.id')
                    ->leftJoin(new Alias(Table::ELEMENTS_OWNERS, 'eo'), 'eo.elementId', 'el.id')
                    ->whereNotNull("t.{$this->fieldForeignKey}")
                    ->whereNull('eo.elementId')
                    ->pluck('el.id');

                // IDs of nested elements where the field no longer exists
                $ids2 = DB::table(Table::ELEMENTS, 'el')
                    ->join(new Alias($this->table, 't'), 't.id', 'el.id')
                    ->leftJoin(new Alias(Table::FIELDS, 'f'), 'f.id', "t.{$this->fieldForeignKey}")
                    ->whereNotNull("t.{$this->fieldForeignKey}")
                    ->whereNull('f.id')
                    ->pluck('el.id');

                $ids = $ids1->merge($ids2)->unique();

                if ($ids->isNotEmpty()) {
                    DB::table(Table::ELEMENTS)
                        ->whereIn('id', $ids)
                        ->delete();
                }
            }
        );
    }
}
