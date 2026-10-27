<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\ImageDuplicates;
use CraftCms\Cms\Database\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable(Table::IMAGEHASHES)) {
            Schema::create(Table::IMAGEHASHES, function (Blueprint $table): void {
                $table->integer('assetId');
                $table->tinyInteger('band');
                $table->char('value', 4);
                $table->primary(['assetId', 'band']);
            });
            Schema::createIndex(Table::IMAGEHASHES, ['band', 'value']);
            Schema::table(Table::IMAGEHASHES, fn (Blueprint $table) => $table->foreign('assetId')->references('id')->on(Table::ASSETS)->cascadeOnDelete());
        }

        // Images sampled before the index existed
        DB::table(Table::ASSETS)
            ->whereNotNull('analysis')
            ->select(['id', 'analysis'])
            ->orderBy('id')
            ->chunk(500, function ($assets): void {
                foreach ($assets as $asset) {
                    $hash = json_decode((string) $asset->analysis, true)['hash'] ?? null;

                    if (is_string($hash)) {
                        app(ImageDuplicates::class)->index((int) $asset->id, $hash);
                    }
                }
            });
    }

    public function down(): void {}
};
