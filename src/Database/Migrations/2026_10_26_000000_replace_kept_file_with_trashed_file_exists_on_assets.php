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
        if (! Schema::hasColumn(Table::ASSETS, 'trashedFileExists')) {
            Schema::table(Table::ASSETS, function (Blueprint $table): void {
                $table->boolean('trashedFileExists')->default(false)->after('deletedWithVolume');
            });
        }

        if (Schema::hasColumn(Table::ASSETS, 'keptFile')) {
            Schema::table(Table::ASSETS, function (Blueprint $table): void {
                $table->dropColumn('keptFile');
            });
        }
    }

    public function down(): void {}
};
