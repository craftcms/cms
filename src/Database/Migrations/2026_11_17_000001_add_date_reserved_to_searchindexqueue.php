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
        if (Schema::hasColumn(Table::SEARCHINDEXQUEUE, 'dateReserved')) {
            return;
        }

        // Reserved rows without a date count as abandoned, so the next index job picks them up again.
        Schema::table(Table::SEARCHINDEXQUEUE, function (Blueprint $table): void {
            $table->dateTime('dateReserved')->nullable()->after('reserved');
        });
    }

    public function down(): void
    {
        Schema::table(Table::SEARCHINDEXQUEUE, function (Blueprint $table): void {
            $table->dropColumn('dateReserved');
        });
    }
};
