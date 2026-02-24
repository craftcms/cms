<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('info', 'maintenance')) {
            return;
        }

        Schema::table('info', function (Blueprint $table) {
            $table->dropColumn('maintenance');
        });
    }

    public function down(): void
    {
        Schema::table('info', function (Blueprint $table) {
            $table->boolean('maintenance')->default(false)->after('schemaVersion');
        });
    }
};
