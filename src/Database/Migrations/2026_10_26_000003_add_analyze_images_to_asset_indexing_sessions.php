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
        if (Schema::hasColumn(Table::ASSETINDEXINGSESSIONS, 'analyzeImages')) {
            return;
        }

        Schema::table(Table::ASSETINDEXINGSESSIONS, function (Blueprint $table): void {
            $table->boolean('analyzeImages')->default(false)->after('listEmptyFolders');
        });
    }

    public function down(): void {}
};
