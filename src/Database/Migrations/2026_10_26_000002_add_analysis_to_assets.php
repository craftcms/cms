<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Analysis\Data\ImageAnalysis;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Json;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columns whose data moves into `analysis`: images’ colors, and, in development builds, what the “Tag images” AI
     * task found.
     */
    private const array COLUMNS = ['colors', 'keywords', 'visibleText'];

    public function up(): void
    {
        if (! Schema::hasColumn(Table::ASSETS, 'analysis')) {
            Schema::table(Table::ASSETS, function (Blueprint $table): void {
                $table->json('analysis')->nullable()->after('focalPoint');
            });
        }

        $columns = array_values(array_filter(self::COLUMNS, fn (string $column): bool => Schema::hasColumn(Table::ASSETS, $column)));

        if ($columns === []) {
            return;
        }

        DB::table(Table::ASSETS)
            ->select(['id', ...$columns])
            ->where(function (Builder $query) use ($columns): void {
                foreach ($columns as $column) {
                    $query->orWhereNotNull($column);
                }
            })
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    $analysis = ImageAnalysis::fromArray([
                        'colors' => isset($row->colors) ? Json::decodeIfJson($row->colors) : null,
                        'keywords' => isset($row->keywords) ? Json::decodeIfJson($row->keywords) : null,
                        'text' => $row->visibleText ?? null,
                    ]);

                    DB::table(Table::ASSETS)
                        ->where('id', $row->id)
                        ->update(['analysis' => Json::encode($analysis->toArray())]);
                }
            });

        Schema::table(Table::ASSETS, function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }

    public function down(): void {}
};
