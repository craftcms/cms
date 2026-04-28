<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('info', 'fieldVersion')) {
            return;
        }

        Schema::table('info', function (Blueprint $table) {
            $table->dropColumn('fieldVersion');
        });
    }

    public function down(): void
    {
        Schema::table('info', function (Blueprint $table) {
            $table->char('fieldVersion', 12)->default('000000000000')->after('configVersion');
        });
    }
};
