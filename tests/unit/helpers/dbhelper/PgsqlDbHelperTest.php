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
 * Unit tests for the DB Helper class.
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

    public function teststuff(){

    }

    protected function _after()
    {
    }
}