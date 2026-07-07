<?php

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\Link;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $linkFieldIds = app(Fields::class)->getAllFields()
            ->filter(fn ($f) => $f instanceof Link)
            ->map(fn (Link $f) => $f->id);

        DB::table(Table::RELATIONS)
            ->whereIn('fieldId', $linkFieldIds)
            ->delete();
    }

    public function down(): void {}
};
