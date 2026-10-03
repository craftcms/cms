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
        if (Schema::hasColumn(Table::SEARCHINDEX, 'layoutElementUid')) {
            return;
        }

        Schema::table(Table::SEARCHINDEX, function (Blueprint $table) {
            $table->dropPrimary();
        });

        Schema::table(Table::SEARCHINDEX, function (Blueprint $table) {
            $table->char('layoutElementUid', 36)->default('0')->after('fieldId');
            $table->primary(['elementId', 'attribute', 'fieldId', 'layoutElementUid', 'siteId']);
        });
    }

    public function down(): void {}
};
