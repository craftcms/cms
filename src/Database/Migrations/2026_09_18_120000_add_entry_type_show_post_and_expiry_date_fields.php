<?php

use CraftCms\Cms\Database\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(Table::ENTRYTYPES, function (Blueprint $table) {
            if (! Schema::hasColumn(Table::ENTRYTYPES, 'showPostDateField')) {
                $table->boolean('showPostDateField')->default(true)->after('showStatusField');
            }

            if (! Schema::hasColumn(Table::ENTRYTYPES, 'showExpiryDateField')) {
                $table->boolean('showExpiryDateField')->default(true)->after('showPostDateField');
            }
        });
    }

    public function down(): void {}
};
