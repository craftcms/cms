<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Query;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Brings entries’ stored statuses up to date, so the `craft:update-element-statuses` command’s first run doesn’t
 * dispatch `ElementStatusChanged` for entries that went live or expired before the upgrade.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = DB::escape(Query::prepareDateForDb(now()->endOfMinute()));
        $postDate = DB::getQueryGrammar()->wrap('postDate');
        $expiryDate = DB::getQueryGrammar()->wrap('expiryDate');
        $status = DB::raw("(CASE WHEN $postDate IS NULL OR $postDate > $now THEN 'pending' WHEN $expiryDate IS NOT NULL AND $expiryDate <= $now THEN 'expired' ELSE 'live' END)");

        DB::table(Table::ENTRIES)
            ->where('status', '!=', $status)
            ->update(['status' => $status]);
    }

    public function down(): void {}
};
