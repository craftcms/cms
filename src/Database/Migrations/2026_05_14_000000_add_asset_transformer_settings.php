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
        if (! Schema::hasColumn(Table::VOLUMES, 'defaultTransformer')) {
            Schema::table(Table::VOLUMES, function (Blueprint $table) {
                $table->string('defaultTransformer')->nullable()->after('transformSubpath');
            });
        }

        if (! Schema::hasColumn(Table::IMAGETRANSFORMS, 'transformer')) {
            Schema::table(Table::IMAGETRANSFORMS, function (Blueprint $table) {
                $table->string('transformer')->nullable()->after('fill');
            });
        }

        if (! Schema::hasColumn(Table::IMAGETRANSFORMS, 'settings')) {
            Schema::table(Table::IMAGETRANSFORMS, function (Blueprint $table) {
                $table->json('settings')->nullable()->after('transformer');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn(Table::IMAGETRANSFORMS, 'settings')) {
            Schema::dropColumns(Table::IMAGETRANSFORMS, 'settings');
        }

        if (Schema::hasColumn(Table::IMAGETRANSFORMS, 'transformer')) {
            Schema::dropColumns(Table::IMAGETRANSFORMS, 'transformer');
        }

        if (Schema::hasColumn(Table::VOLUMES, 'defaultTransformer')) {
            Schema::dropColumns(Table::VOLUMES, 'defaultTransformer');
        }
    }
};
