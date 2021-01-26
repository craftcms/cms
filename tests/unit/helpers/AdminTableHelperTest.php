<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use craft\helpers\AdminTable;
use UnitTester;

/**
 * Unit tests for the Admin Table Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class AdminTableHelperTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @dataProvider paginationLinksDataProvider
     *
     * @param array $expected
     * @param int $page
     * @param int $total
     * @param int $limit
     */
    public function testPaginationLinks(array $expected, int $page, int $total, int $limit)
    {
        self::assertSame($expected, AdminTable::paginationLinks($page, $total, $limit));
    }

    /**
     * @return array
     */
    public function paginationLinksDataProvider(): array
    {
        return [
            [
                [
                    'total' => 100,
                    'per_page' => 10,
                    'current_page' => 5,
                    'last_page' => 10,
                    'next_page_url' => '?next',
                    'prev_page_url' => '?prev',
                    'from' => 41,
                    'to' => 50,
                ], 5, 100, 10
            ],
        ];
    }
}
