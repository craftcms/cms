<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Query;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Brings entries’ stored statuses up to date, so the `craft:update-element-statuses` task’s first run doesn’t
 * dispatch `ElementStatusChanged` for entries that went live or expired before the upgrade.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = Query::prepareDateForDb(now()->endOfMinute());

        DB::table(Table::ENTRIES)
            ->where(fn (Builder $query) => $query->whereNull('postDate')->orWhere('postDate', '>', $now))
            ->where('status', '!=', 'pending')
            ->update(['status' => 'pending']);

        DB::table(Table::ENTRIES)
            ->where('postDate', '<=', $now)
            ->whereNotNull('expiryDate')
            ->where('expiryDate', '<=', $now)
            ->where('status', '!=', 'expired')
            ->update(['status' => 'expired']);

        DB::table(Table::ENTRIES)
            ->where('postDate', '<=', $now)
            ->where(fn (Builder $query) => $query->whereNull('expiryDate')->orWhere('expiryDate', '>', $now))
            ->where('status', '!=', 'live')
            ->update(['status' => 'live']);
    }

    public function down(): void {}
};
