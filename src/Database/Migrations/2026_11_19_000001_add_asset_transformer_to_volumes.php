<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Volumes choose their asset transform driver.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table(Table::VOLUMES, function (Blueprint $table): void {
            if (! Schema::hasColumn(Table::VOLUMES, 'assetTransformer')) {
                $table->string('assetTransformer')->nullable()->after('subpath');
            }
        });
    }

    public function down(): void {}
};
