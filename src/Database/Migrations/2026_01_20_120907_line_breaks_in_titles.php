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
        if (Schema::hasColumn(Table::ENTRYTYPES, 'allowLineBreaksInTitles')) {
            return;
        }

        Schema::table(Table::ENTRYTYPES, function (Blueprint $table) {
            $table->boolean('allowLineBreaksInTitles')->default(false)->after('titleFormat');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn(Table::ENTRYTYPES, 'allowLineBreaksInTitles')) {
            return;
        }

        Schema::dropColumns(Table::ENTRYTYPES, 'allowLineBreaksInTitles');
    }
};
