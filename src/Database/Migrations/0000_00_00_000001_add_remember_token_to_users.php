<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn(Table::USERS, 'rememberToken')) {
            return;
        }

        Schema::table(Table::USERS, function (Blueprint $table) {
            $table->string('rememberToken', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropColumns(Table::USERS, 'rememberToken');
    }
};
