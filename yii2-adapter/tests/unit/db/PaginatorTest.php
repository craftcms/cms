<?php
/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\db;

use Carbon\Carbon;
use craft\db\Paginator;
use craft\db\Query;
use craft\test\TestCase;
use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaginatorTest extends TestCase
{
    public function testItWrapsALaravelPaginator(): void
    {
        $this->insertSites(4);

        $paginator = new Paginator((new Query())->from(Table::SITES), [
            'pageSize' => 2,
            'currentPage' => 2,
        ]);

        self::assertSame(5, $paginator->getTotalResults());
        self::assertSame(3, $paginator->getTotalPages());
        self::assertCount(2, $paginator->getPageResults());
        self::assertSame(2, $paginator->getPaginator()->currentPage());
        self::assertSame(2, $paginator->getPaginator()->perPage());
    }

    public function testItKeepsTotalSemanticsForLimitedQueries(): void
    {
        $this->insertSites(5);

        $paginator = new Paginator(
            (new Query())
                ->from(Table::SITES)
                ->limit(3)
                ->offset(2),
            [
                'pageSize' => 2,
                'currentPage' => 1,
            ],
        );

        self::assertSame(3, $paginator->getTotalResults());
        self::assertSame(2, $paginator->getTotalPages());
        self::assertCount(2, $paginator->getPageResults());
    }

    private function insertSites(int $count): void
    {
        $groupId = DB::table(Table::SITES)
            ->value('groupId');
        $timestamp = Carbon::now();

        foreach (range(1, $count) as $index) {
            DB::table(Table::SITES)->insert([
                'groupId' => $groupId,
                'primary' => false,
                'enabled' => true,
                'name' => "Site {$index}",
                'handle' => "site{$index}-" . Str::lower(Str::random(6)),
                'language' => 'en-US',
                'hasUrls' => false,
                'baseUrl' => null,
                'sortOrder' => $index + 1,
                'dateCreated' => $timestamp,
                'dateUpdated' => $timestamp,
                'uid' => (string) Str::uuid(),
            ]);
        }
    }
}
