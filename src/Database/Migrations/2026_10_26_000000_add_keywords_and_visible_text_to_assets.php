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
        Schema::table(Table::ASSETS, function (Blueprint $table) {
            if (! Schema::hasColumn(Table::ASSETS, 'keywords')) {
                $table->json('keywords')->nullable()->after('colors');
            }

            if (! Schema::hasColumn(Table::ASSETS, 'visibleText')) {
                $table->text('visibleText')->nullable()->after('keywords');
            }
        });
    }

    public function down(): void {}
};
