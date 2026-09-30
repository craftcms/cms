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
        if (! Schema::hasColumn(Table::ASSETS, 'colors')) {
            Schema::table(Table::ASSETS, function (Blueprint $table) {
                $table->json('colors')->nullable()->after('focalPoint');
            });
        }

        // Superseded by `colors` before it was released; images are sampled again as they're indexed.
        if (Schema::hasColumn(Table::ASSETS, 'dominantColor')) {
            Schema::table(Table::ASSETS, function (Blueprint $table) {
                $table->dropColumn('dominantColor');
            });
        }
    }

    public function down(): void {}
};
