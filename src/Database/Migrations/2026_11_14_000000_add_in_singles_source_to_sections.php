<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the setting that decides whether a single is listed in the shared
 * “Singles” entry source or gets a source of its own.
 *
 * Existing singles stay in the Singles source, so nothing moves. Their project
 * config has no value for it, which reads the same way, so none is written.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn(Table::SECTIONS, 'inSinglesSource')) {
            return;
        }

        Schema::table(Table::SECTIONS, function (Blueprint $table) {
            $table->boolean('inSinglesSource')->default(true)->after('enableVersioning');
        });
    }

    public function down(): void
    {
        Schema::table(Table::SECTIONS, function (Blueprint $table) {
            $table->dropColumn('inSinglesSource');
        });
    }
};
