<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\Widgets\AboutCraft;
use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Query;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Replaces Updates widgets with the “About Craft CMS” widget, which now says whether updates are available, in their
 * place on each user’s dashboard. Dashboards without an Updates widget are left as they are; users who haven’t saved
 * their dashboard yet get it with the rest of the default widgets.
 */
return new class extends Migration
{
    private const string UPDATES = 'CraftCms\\Cms\\Dashboard\\Widgets\\Updates';

    private const string ABOUT = AboutCraft::class;

    public function up(): void
    {
        $now = Query::prepareDateForDb(now());

        // A user can only have one, so an Updates widget beside an existing one is dropped. The IDs are fetched first, as
        // MySQL can’t delete from a table it’s reading in a subquery.
        $userIdsWithAbout = DB::table(Table::WIDGETS)->where('type', self::ABOUT)->distinct()->pluck('userId');

        $userIdsWithAbout->chunk(1000)->each(static fn (Collection $userIds) => DB::table(Table::WIDGETS)
            ->where('type', self::UPDATES)
            ->whereIn('userId', $userIds)
            ->delete());

        DB::table(Table::WIDGETS)
            ->where('type', self::UPDATES)
            ->update(['type' => self::ABOUT, 'settings' => '[]', 'dateUpdated' => $now]);
    }

    public function down(): void {}
};
