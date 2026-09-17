<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database;

use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use stdClass;

#[Singleton]
class LaravelMigrations
{
    public function ensureNotificationsTable(): void
    {
        if (Schema::hasTable('notifications')) {
            return;
        }

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

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

    /**
     * Converts a Yii-era migration table (`name`, `applyTime`) to the Laravel
     * format (`migration`, `batch`), keeping the applied history.
     */
    public function ensureMigrationTableFormat(): void
    {
        if (! Schema::hasTable(Table::MIGRATIONS) || Schema::hasColumn(Table::MIGRATIONS, 'migration')) {
            return;
        }

        $history = DB::table(Table::MIGRATIONS)->get();

        Schema::drop(Table::MIGRATIONS);

        app(Migrator::class)->getRepository()->createRepository();

        $rows = $history
            ->map(fn (stdClass $migration): array => [
                'migration' => $this->migrationName($migration),
                'track' => ($migration->track ?? null) === 'content' ? null : $migration->track,
                'batch' => 1,
            ])
            ->all();

        if ($rows === []) {
            return;
        }

        DB::table(Table::MIGRATIONS)->insert($rows);
    }

    /**
     * Craft's own migrations were renamed from Yii's `mYYMMDD_HHMMSS_name` to
     * Laravel's `YYYY_MM_DD_HHMMSS_name`. Plugin tracks keep whatever they were
     * applied under, so their still-Yii-named files continue to match.
     */
    private function migrationName(stdClass $migration): string
    {
        $name = (string) $migration->name;

        if (($migration->track ?? null) !== 'craft') {
            return $name;
        }

        return preg_replace('/^m(\d{2})(\d{2})(\d{2})_(\d{6})_/', '20$1_$2_$3_$4_', $name) ?? $name;
    }
}
