<?php

use CraftCms\Cms\Database\Migrations\Migration;
use CraftCms\Cms\Database\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** @since 6.0.0 */
return new class extends Migration
{
    public function up(): bool
    {
        Schema::table(Table::USERS, function (Blueprint $table) {
            $table->rememberToken();
        });

        return true;
    }

    public function down(): bool
    {
        Schema::dropColumns(Table::USERS, 'remember_token');

        return true;
    }
};
