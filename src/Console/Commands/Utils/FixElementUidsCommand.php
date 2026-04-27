<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands\Utils;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Override;

class FixElementUidsCommand extends Command
{
    use CraftCommand;

    #[Override]
    protected $signature = 'craft:utils:fix-element-uids';

    #[Override]
    protected $description = 'Ensures all element UIDs are unique.';

    #[Override]
    protected $aliases = ['utils/fix-element-uids', 'utils/fix-element-uids:index', 'utils/fix-element-uids/index'];

    public function handle(Connection $connection): int
    {
        $duplicateUidRowsQuery = $this->duplicateUidRowsQuery($connection);
        $total = $this->duplicateUidRowsQuery($connection)->count();

        if ($total === 0) {
            $this->components->success('No elements with duplicate UIDs found.');

            return self::SUCCESS;
        }

        $this->components->info("Found $total elements with duplicate UIDs.");

        $seenUids = [];

        $duplicateUidRowsQuery
            ->orderBy('id')
            ->each(function (object $row) use (&$seenUids, $connection) {
                if (isset($seenUids[$row->uid])) {
                    $newUid = Str::uuid();

                    $this->components->task(
                        "Changing {$row->uid} ({$row->id}) to $newUid",
                        function () use ($connection, $newUid, $row) {
                            $connection->table(Table::ELEMENTS)
                                ->where('id', $row->id)
                                ->update(['uid' => $newUid]);
                        }
                    );
                }

                $seenUids[$row->uid] = true;
            });

        $this->components->success('Finished assigning unique UIDs to all elements.');

        return self::SUCCESS;
    }

    private function duplicateUidRowsQuery(Connection $connection): Builder
    {
        return $connection->table(Table::ELEMENTS)
            ->select('id', 'uid')
            ->whereIn('uid', function (Builder $query) {
                $query->from(Table::ELEMENTS)
                    ->select('uid')
                    ->groupBy('uid')
                    ->havingRaw('count(uid) > 1');
            });
    }
}
