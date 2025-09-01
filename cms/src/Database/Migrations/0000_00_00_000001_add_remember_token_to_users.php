<?php

use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** @since 6.0.0 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn(Table::USERS, 'remember_token')) {
            return;
        }

        Schema::table(Table::USERS, function (Blueprint $table) {
            $table->rememberToken();
        });
    }

    public function down(): void
    {
        Schema::dropColumns(Table::USERS, 'remember_token');
    }
};
