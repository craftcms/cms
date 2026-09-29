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
            if (! Schema::hasColumn(Table::ASSETS, 'dominantColor')) {
                $table->string('dominantColor', 7)->nullable()->after('focalPoint');
            }
        });
    }

    public function down(): void {}
};
