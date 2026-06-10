<?php

use CraftCms\Cms\Database\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
