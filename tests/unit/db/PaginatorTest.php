<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\db;

use Codeception\Test\Unit;
use craft\db\Paginator;
use craft\db\Query;
use craft\db\Table;
use craft\records\Session;
use UnitTester;

/**
 * Unit tests for Paginator
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class PaginatorTest extends Unit
{
    // Public properties
    // =========================================================================

    /**
     * @var Paginator
     */
    private $paginator;

    /**
     * @var UnitTester
     */
    protected $tester;

    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     *
     */
    public function testTotalResults()
    {
        $this->setPaginator([], [], 10);
        $this->assertSame('10', (string)$this->paginator->getTotalResults());
    }

    /**
     *
     */
    public function testTotalResultsWithQueryLimit()
    {
        $this->setPaginator(['limit' => 10], [], 25);
        $this->assertSame(10, $this->paginator->getTotalResults());
    }

    /**
     *
     */
    public function testTotalResultsWithQueryOffset()
    {
        $this->setPaginator(['offset' => 5], [], 10);
        $this->assertSame(5, $this->paginator->getTotalResults());
    }

    /**
     *
     */
    public function testTotalPages()
    {
        $this->setPaginator([], ['pageSize' => '25']);
        $this->assertSame(4, $this->paginator->getTotalPages());
    }

    /**
     *
     */
    public function testTotalPagesWithOneOverflow()
    {
        $this->setPaginator([], ['pageSize' => '25'], 101);
        $this->assertSame(5, $this->paginator->getTotalPages());
    }

    /**
     *
     */
    public function testGetPageResults()
    {
        $this->setPaginator([], ['pageSize' => '2']);

        $desiredResults = (new Query())->from([Table::SESSIONS])->limit(2)->all();
        $this->assertSame($desiredResults, $this->paginator->getPageResults());
    }

    /**
     *
     */
    public function testGetPageResultsSlices()
    {
        $this->setPaginator([], ['pageSize' => '2'], 10);

        $desiredResults = (new Query())->from(Table::SESSIONS)->limit(4)->all();

        // Should get the first two...
        $this->assertSame([$desiredResults[0], $desiredResults[1]], $this->paginator->getPageResults());

        // Next page. Other two results.
        $this->paginator->setCurrentPage(2);
        $this->assertSame([$desiredResults[2], $desiredResults[3]], $this->paginator->getPageResults());
    }

    /**
     *
     */
    public function testGetPageResultsIncompleteResults()
    {
        $this->setPaginator([], ['pageSize' => '2'], 1);

        $desiredResults = (new Query())->from([Table::SESSIONS])->limit(1)->all();
        $this->assertSame($desiredResults, $this->paginator->getPageResults());
    }

    /**
     *
     */
    public function testGetPageResultsNoPageSize()
    {
        $this->setPaginator([], ['pageSize' => null], 10);
        $this->assertSame([], $this->paginator->getPageResults());
    }

    /**
     *
     */
    public function testGetPageOffset()
    {
        $this->setPaginator([], [], 10);
        $this->assertSame(0, $this->paginator->getPageOffset());
    }

    /**
     *
     */
    public function testSetPageResultValidation()
    {
        $this->setPaginator([], [], 10);
        $this->paginator->setCurrentPage(5);
        $this->assertSame(1, $this->paginator->getCurrentPage());
    }

    /**
     *
     */
    public function testSetPageResultValidationLastPage()
    {
        $this->setPaginator([], ['pageSize' => '5'], 10);
        $this->paginator->setCurrentPage(2);
        $this->assertSame(2, $this->paginator->getCurrentPage());

        $this->paginator->setCurrentPage(3);
        $this->assertSame(2, $this->paginator->getCurrentPage());
    }

    // Protected Methods
    // =========================================================================

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
}
