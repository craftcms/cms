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
        if (Schema::hasColumn(Table::WIDGETS, 'color')) {
            return;
        }

        // Existing widgets keep the gray they've always had; null means no color.
        Schema::table(Table::WIDGETS, function (Blueprint $table): void {
            $table->string('color')->nullable()->default('gray')->after('colspan');
        });
    }

    public function down(): void {}
};
