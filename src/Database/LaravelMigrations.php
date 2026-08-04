<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database;

use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

#[Singleton]
class LaravelMigrations
{
    public function ensureSessionsTable(): void
    {
        if (Schema::hasTable(Table::SESSIONS)) {
            return;
        }

        Schema::create(Table::SESSIONS, function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function ensureMigrationTableTrackColumn(): void
    {
        if (! Schema::hasTable(Table::MIGRATIONS) || Schema::hasColumn(Table::MIGRATIONS, 'track')) {
            return;
        }

        Schema::table(Table::MIGRATIONS, function (Blueprint $table) {
            $table->string('track')->nullable()->after('id');
        });
    }
}
