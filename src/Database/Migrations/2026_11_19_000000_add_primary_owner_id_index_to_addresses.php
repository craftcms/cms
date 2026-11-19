<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sites upgraded from Craft 5.13 or later already have this index.
        if (Schema::hasIndex(Table::ADDRESSES, ['primaryOwnerId'])) {
            return;
        }

        Schema::createIndex(Table::ADDRESSES, ['primaryOwnerId']);
    }

    public function down(): void {}
};
