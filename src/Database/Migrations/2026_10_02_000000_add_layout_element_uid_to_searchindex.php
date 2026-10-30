<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn(Table::SEARCHINDEX, 'layoutElementUid')) {
            return;
        }

        if (DB::isPgsql()) {
            // Laravel assumes `<table>_pkey`, but Craft 3 and 4 installs named the constraint differently.
            $table = DB::getQueryGrammar()->wrapTable(Table::SEARCHINDEX);
            $primaryKey = DB::scalar("select conname from pg_constraint where contype = 'p' and conrelid = ?::regclass", [$table]);
            DB::statement(sprintf('alter table %s drop constraint %s', $table, DB::getQueryGrammar()->wrap($primaryKey)));
        } else {
            Schema::table(Table::SEARCHINDEX, fn (Blueprint $table) => $table->dropPrimary());
        }

        Schema::table(Table::SEARCHINDEX, function (Blueprint $table) {
            $table->char('layoutElementUid', 36)->default('0')->after('fieldId');
            $table->primary(['elementId', 'attribute', 'fieldId', 'layoutElementUid', 'siteId']);
        });
    }

    public function down(): void {}
};
