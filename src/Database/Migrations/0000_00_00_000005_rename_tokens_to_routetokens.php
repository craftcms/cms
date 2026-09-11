<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable(Table::ROUTETOKENS)) {
            Schema::dropIfExists('tokens');

            return;
        }

        Schema::rename('tokens', Table::ROUTETOKENS);
    }

    public function down(): void
    {
        Schema::rename(Table::ROUTETOKENS, 'tokens');
    }
};
