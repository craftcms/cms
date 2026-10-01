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
        if (! Schema::hasColumn(Table::SECTIONS_SITES, 'route')) {
            Schema::table(Table::SECTIONS_SITES, function (Blueprint $table): void {
                $table->string('route', 500)->nullable()->after('template');
            });
        }
    }

    public function down(): void {}
};
