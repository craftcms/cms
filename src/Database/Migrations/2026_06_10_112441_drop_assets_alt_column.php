<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn(Table::ASSETS, 'alt')) {
            return;
        }

        // Find assets that have globally-defined alt text and null/missing translated alt text,
        // and fill in their translated values.

        $rows = DB::table(Table::ASSETS)
            // fetch assets_sites.assetId so we know whether the row already exists
            ->select(['assets.id', 'elements_sites.siteId', 'assets.alt', 'assets_sites.assetId as assetSiteId'])
            ->join(Table::ELEMENTS_SITES, 'elements_sites.elementId', '=', 'assets.id')
            ->leftJoin(Table::ASSETS_SITES, function ($join) {
                $join->on('assets_sites.assetId', '=', 'assets.id')
                    ->on('assets_sites.siteId', '=', 'elements_sites.siteId');
            })
            ->whereNotNull('assets.alt')
            ->where('assets.alt', '!=', '')
            ->whereNull('assets_sites.alt')
            ->get();

        foreach ($rows as $row) {
            // If the assets_sites.assetId value came back, the row already exists
            if ($row->assetSiteId !== null) {
                DB::table(Table::ASSETS_SITES)
                    ->where('assetId', $row->id)
                    ->where('siteId', $row->siteId)
                    ->update(['alt' => $row->alt]);
            } else {
                DB::table(Table::ASSETS_SITES)->insert([
                    'assetId' => $row->id,
                    'siteId' => $row->siteId,
                    'alt' => $row->alt,
                ]);
            }
        }

        Schema::table(Table::ASSETS, function (Blueprint $table) {
            $table->dropColumn('alt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->output->error('2026_06_10_112441_drop_assets_alt_column cannot be reverted.');
    }
};
