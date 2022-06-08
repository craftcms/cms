<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\db;

use Codeception\Test\Unit;
use Craft;
use craft\db\Paginator;
use craft\db\Query;
use craft\db\Table;
use craft\records\Session;
use craft\test\TestCase;
use UnitTester;
use yii\db\Exception;

/**
 * Unit tests for Paginator
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class PaginatorTest extends TestCase
{
    /**
     * @var Paginator
     */
    private Paginator $paginator;

    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     *
     */
    public function testTotalResults(): void
    {
        $this->setPaginator([], [], 10);
        self::assertSame('10', (string)$this->paginator->getTotalResults());
        $this->resetPaginator();
    }

    /**
     *
     */
    public function testTotalResultsWithQueryLimit(): void
    {
        $this->setPaginator(['limit' => 10], [], 25);
        self::assertSame(10, $this->paginator->getTotalResults());
        $this->resetPaginator();
    }

    /**
     *
     */
    public function testTotalResultsWithQueryOffset(): void
    {
        $this->setPaginator(['offset' => 5], [], 10);
        self::assertSame(5, $this->paginator->getTotalResults());
        $this->resetPaginator();
    }

    /**
     *
     */
    public function testTotalPages(): void
    {
        $this->setPaginator([], ['pageSize' => '25']);
        self::assertSame(4, $this->paginator->getTotalPages());
        $this->resetPaginator();
    }

    /**
     *
     */
    public function testTotalPagesWithOneOverflow(): void
    {
        $this->setPaginator([], ['pageSize' => '25'], 101);
        self::assertSame(5, $this->paginator->getTotalPages());
        $this->resetPaginator();
    }

    /**
     *
     */
    public function testGetPageResults(): void
    {
        $this->setPaginator([], ['pageSize' => '2']);

        $desiredResults = (new Query())->from([Table::SESSIONS])->limit(2)->all();
        self::assertSame($desiredResults, $this->paginator->getPageResults());
        $this->resetPaginator();
    }

    /**
     *
     */
    public function testGetPageResultsSlices(): void
    {
        $this->setPaginator([], ['pageSize' => '2'], 10);

        $desiredResults = (new Query())->from(Table::SESSIONS)->limit(4)->all();

        // Should get the first two...
        self::assertSame([$desiredResults[0], $desiredResults[1]], $this->paginator->getPageResults());

        // Next page. Other two results.
        $this->paginator->setCurrentPage(2);
        self::assertSame([$desiredResults[2], $desiredResults[3]], $this->paginator->getPageResults());
        $this->resetPaginator();
    }

    /**
     *
     */
    public function testGetPageResultsIncompleteResults(): void
    {
        $this->setPaginator([], ['pageSize' => '2'], 1);

        $desiredResults = (new Query())->from([Table::SESSIONS])->limit(1)->all();
        self::assertSame($desiredResults, $this->paginator->getPageResults());
        $this->resetPaginator();
    }

    /**
     *
     */
    public function testGetPageOffset(): void
    {
        $this->setPaginator([], [], 10);
        self::assertSame(0, $this->paginator->getPageOffset());
        $this->resetPaginator();
    }

    /**
     *
     */
    public function testSetPageResultValidation(): void
    {
        $this->setPaginator([], [], 10);
        $this->paginator->setCurrentPage(5);
        self::assertSame(1, $this->paginator->getCurrentPage());
        $this->resetPaginator();
    }

    /**
     *
     */
    public function testSetPageResultValidationLastPage(): void
    {
        $this->setPaginator([], ['pageSize' => '5'], 10);
        $this->paginator->setCurrentPage(2);
        self::assertSame(2, $this->paginator->getCurrentPage());

        $this->paginator->setCurrentPage(3);
        self::assertSame(2, $this->paginator->getCurrentPage());
        $this->resetPaginator();
    }

    /**
     * @param array $queryParams
     * @param array $config
     * @param int $requiredSessions
     * @return void
     */
    protected function setPaginator(array $queryParams = [], array $config = [], int $requiredSessions = 100)
    {
        $this->tester->haveMultiple(Session::class, $requiredSessions);

        $query = (new Query())->from(Table::SESSIONS);
        foreach ($queryParams as $key => $value) {
            $query->$key = $value;
        }

        $this->paginator = new Paginator(
            $query,
            $config
        );
    }

    /**
     * @throws Exception
     */
    protected function resetPaginator()
    {
        Craft::$app->getDb()->createCommand()
            ->truncateTable(Table::SESSIONS)
            ->execute();
    }
}
