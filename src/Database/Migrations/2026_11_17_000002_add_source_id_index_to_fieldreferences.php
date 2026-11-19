<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // relatedTo() looks up references by source when `field` is `*`.
        if (Schema::hasIndex(Table::FIELDREFERENCES, ['sourceId'])) {
            return;
        }

        Schema::createIndex(Table::FIELDREFERENCES, ['sourceId']);
    }

    public function down(): void {}
};
