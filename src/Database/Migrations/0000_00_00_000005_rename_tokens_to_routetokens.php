<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('routetokens')) {
            Schema::dropIfExists('tokens');

            return;
        }

        Schema::rename('tokens', 'routetokens');
    }

    public function down(): void
    {
        Schema::rename('routetokens', 'tokens');
    }
};
