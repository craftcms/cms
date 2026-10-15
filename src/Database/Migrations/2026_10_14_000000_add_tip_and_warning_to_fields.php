<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sites updating from Craft 5.12+ already have these columns.
        if (! Schema::hasColumn(Table::FIELDS, 'tip')) {
            Schema::table(Table::FIELDS, function (Blueprint $table) {
                $table->text('tip')->nullable()->after('instructions');
            });
        }

        if (! Schema::hasColumn(Table::FIELDS, 'warning')) {
            Schema::table(Table::FIELDS, function (Blueprint $table) {
                $table->text('warning')->nullable()->after('tip');
            });
        }
    }

    public function down(): void {}
};
