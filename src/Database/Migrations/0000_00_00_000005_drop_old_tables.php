<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'categorygroups_sites',
        'categorygroups',
        'categories',
        'globalsets',
        'tags',
        'taggroups',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        $this->output->writeln('0000_00_00_000005_drop_old_tables migration cannot be reverted.');
    }
};
