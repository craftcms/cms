<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use craft\base\ElementInterface;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\GarbageCollection\GarbageCollection;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\Alias;

/**
 * Deletes elements that are missing data in the given element extension table.
 */
final class DeletePartialElements extends GarbageCollectionAction
{
    public function __construct(
        GarbageCollection $garbageCollection,
        GeneralConfig $generalConfig,
        /** @var class-string<ElementInterface> */
        private readonly string $elementType,
        private readonly string $table,
        private readonly string $foreignKey = 'id',
    ) {
        parent::__construct($garbageCollection, $generalConfig);
    }

    public function __invoke(): void
    {
        $this->components->task(
            sprintf('deleting partial %s data', $this->elementType::lowerDisplayName()),
            function () {
                DB::table(Table::ELEMENTS, 'e')
                    ->leftJoin(new Alias($this->table, 't'), "t.{$this->foreignKey}", 'e.id')
                    ->where('e.type', $this->elementType)
                    ->whereNull("t.{$this->foreignKey}")
                    ->pluck('e.id')
                    ->chunk($this->garbageCollection::CHUNK_SIZE)
                    ->each(function ($idsChunk) {
                        DB::table(Table::ELEMENTS)
                            ->whereIn('id', $idsChunk)
                            ->delete();
                    });
            }
        );
    }
}
