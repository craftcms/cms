<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\db;

use Codeception\Test\Unit;
use craft\db\Paginator;
use craft\db\Query;
use craft\records\Session;

/**
 * Unit tests for Paginator
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.1
 */
class PaginatorTest extends Unit
{
    // Public properties
    // =========================================================================

    /**
     * @var Paginator $paginator
     */
    private $paginator;

    /**
     * @var \UnitTester $tester
     */
    protected $tester;

    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================


    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function _before()
    {
        parent::_before();

        $this->tester->haveMultiple(Session::class, 100);

        $this->paginator = new Paginator(
            (new Query())->select('*')->from(Session::tableName()),
            []
        );
    }
}
