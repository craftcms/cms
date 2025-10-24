<?php

declare(strict_types=1);

namespace CraftCms\Cms\GarbageCollection\Actions;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\GarbageCollection\GarbageCollection;
use Illuminate\Support\Facades\DB;
use Tpetry\QueryExpressions\Language\Alias;

/**
 * Deletes field layouts that are no longer used.
 */
final class DeleteOrphanedFieldLayouts extends GarbageCollectionAction
{
    public function __construct(
        GarbageCollection $garbageCollection,
        GeneralConfig $generalConfig,
        /** @var class-string<\craft\base\ElementInterface> */
        private readonly string $elementType,
        private readonly string $table,
        private readonly string $foreignKey = 'fieldLayoutId',
    ) {
        parent::__construct($garbageCollection, $generalConfig);
    }

    public function __invoke(): void
    {
        $this->components->task(
            sprintf('deleting orphaned %s field layouts', $this->elementType::lowerDisplayName()),
            function () {
                DB::table(Table::FIELDLAYOUTS, 'fl')
                    ->leftJoin(new Alias($this->table, 't'), "t.{$this->foreignKey}", 'fl.id')
                    ->where('fl.type', $this->elementType)
                    ->whereNull("t.{$this->foreignKey}")
                    ->pluck('fl.id')
                    ->chunk($this->garbageCollection::CHUNK_SIZE)
                    ->each(function ($idsChunk) {
                        DB::table(Table::FIELDLAYOUTS)
                            ->whereIn('id', $idsChunk)
                            ->delete();
                    });
            }
        );
    }
}
