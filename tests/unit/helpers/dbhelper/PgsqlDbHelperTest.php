<?php
/**
 * Created by PhpStorm.
 * User: Giel Tettelaar PC
 * Date: 10/5/2018
 * Time: 10:46 AM
 */

namespace craftunit\helpers;


use Codeception\Test\Unit;

/**
 * Unit tests for the DB Helper class where its output may need to be pgsql specific. Will be skipped if db isnt pgsql.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class PgsqlDbHelperTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        if (getenv('TEST_DB_DRIVER') !== 'pgsql') {
            $this->markTestSkipped();
        }
    }

    protected function _after()
    {
    }
}