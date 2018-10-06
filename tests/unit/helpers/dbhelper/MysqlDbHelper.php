<?php

namespace craftunit\helpers;


use Codeception\Test\Unit;

/**
 * Unit tests for the DB Helper class where its output may need to be mysql specific. Will be skipped if db isnt mysql.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class MysqlDbHelper extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        if (getenv('TEST_DB_DRIVER') !== 'mysql') {
            $this->markTestSkipped();
        }
    }

    protected function _after()
    {
    }
}