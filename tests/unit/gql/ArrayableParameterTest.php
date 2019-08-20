<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql;

use Codeception\Test\Unit;
use craft\gql\base\ElementResolver as BaseResolver;

class ArrayableParameterTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // Tests
    // =========================================================================

    /**
     * Test an arrayable string is split by comma
     *
     * @dataProvider arrayableDataProvider
     */
    public function testArrayableParameters($in, $out, $result)
    {
        if ($result) {
            $this->assertEquals(BaseResolver::prepareArguments($in), $out);
        } else {
            $this->assertNotEquals(BaseResolver::prepareArguments($in), $out);
        }
    }


    // Data Providers
    // =========================================================================

    public function arrayableDataProvider()
    {
        return [
            [['siteId' => '8, 12, 44'], ['siteId' => [8,12,44]], true],
            [['siteId' => '8, 12, 44'], ['siteId' => ['8','12','44']], true],
            [['siteId' => 'longstring'], ['siteId' => ['longstring']], false],
            [['siteId' => 'longstring'], ['siteId' => 'longstring'], true],
            [['siteId' => '*'], ['siteId' => '*'], true],
        ];
    }
}