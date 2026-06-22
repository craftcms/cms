<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Facades\Sections;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn(Table::SECTIONS, 'minAuthors')) {
            return;
        }

        Schema::table(Table::SECTIONS, function (Blueprint $table) {
            $table->unsignedSmallInteger('minAuthors')->default(1)->after('enableVersioning');
        });

        foreach (Sections::getAllSections() as $section) {
            $section->minAuthors = $section->maxAuthors === 0 ? 0 : 1;
            Sections::saveSection($section);
        }
    }

    public function down(): void
    {
        $this->output->error('2026_04_01_155236_min_authors_setting cannot be reverted.');
    }
};
