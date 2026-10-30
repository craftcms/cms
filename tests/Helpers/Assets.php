<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drops the assets table’s generated imageType column and its index, which depend on the analysis column, so tests
 * can put the table back how earlier builds left it.
 */
function dropAssetImageTypeColumn(): void
{
    if (! Schema::hasColumn(Table::ASSETS, 'imageType')) {
        return;
    }

    Schema::table(Table::ASSETS, function (Blueprint $table) {
        $table->dropIndex(Schema::indexName(Table::ASSETS, ['imageType']));
    });
    Schema::table(Table::ASSETS, function (Blueprint $table) {
        $table->dropColumn('imageType');
    });
}
