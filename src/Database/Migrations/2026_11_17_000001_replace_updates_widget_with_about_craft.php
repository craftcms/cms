<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\Widgets\AboutCraft;
use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Replaces Updates widgets with the “About Craft CMS” widget, which now says whether updates are available, in their
 * place on each user’s dashboard. Admins with a saved dashboard and no Updates widget get it at the end of theirs.
 * Users who haven’t saved their dashboard yet get it with the rest of the default widgets.
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

        DB::table(Table::USERS)
            ->where('admin', true)
            ->where('hasDashboard', true)
            ->whereNotExists(static fn ($query) => $query
                ->from(Table::WIDGETS)
                ->whereColumn(Table::WIDGETS.'.userId', Table::USERS.'.id')
                ->where(Table::WIDGETS.'.type', self::ABOUT))
            ->orderBy('id')
            ->chunkById(100, function (Collection $users) use ($now): void {
                $grammar = DB::getQueryGrammar();
                $sortOrders = DB::table(Table::WIDGETS)
                    ->select('userId')
                    ->selectRaw('MAX('.$grammar->wrap('sortOrder').') as '.$grammar->wrap('maxSortOrder'))
                    ->whereIn('userId', $users->pluck('id'))
                    ->groupBy('userId')
                    ->pluck('maxSortOrder', 'userId');

                DB::table(Table::WIDGETS)->insert($users->map(static fn (object $user): array => [
                    'userId' => $user->id,
                    'type' => self::ABOUT,
                    'sortOrder' => (int) ($sortOrders[$user->id] ?? 0) + 1,
                    'color' => 'gray',
                    'settings' => '[]',
                    'dateCreated' => $now,
                    'dateUpdated' => $now,
                    'uid' => Str::uuid()->toString(),
                ])->all());
            });
    }

    public function down(): void {}
};
