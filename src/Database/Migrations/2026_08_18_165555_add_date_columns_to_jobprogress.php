<?php

use CraftCms\Cms\Database\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobprogress', function (Blueprint $table) {
            if (! Schema::hasColumn(Table::JOBPROGRESS, 'dateCompleted')) {
                $table->dateTime('dateCompleted')->nullable()->after('error');
            }

            if (! Schema::hasColumn(Table::JOBPROGRESS, 'dateFailed')) {
                $table->dateTime('dateFailed')->nullable()->after('dateCompleted');
            }
        });
    }

    public function down(): void {}
};
