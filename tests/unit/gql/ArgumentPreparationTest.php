<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql;

use Codeception\Test\Unit;
use craft\gql\base\ElementResolver;

class ArgumentPreparationTest extends Unit
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

    /**
     * Test an arrayable string is split by comma
     *
     * @dataProvider argumentPreparationDataProvider
     */
    public function testArgumentPreparation($in, $out, $result)
    {
        if ($result) {
            $this->assertEquals(ElementResolver::prepareArguments($in), $out);
        } else {
            $this->assertNotEquals(ElementResolver::prepareArguments($in), $out);
        }
    }


    public function argumentPreparationDataProvider()
    {
        return [
            [['siteId' => '8, 12, 44'], ['siteId' => [8, 12, 44]], true],
            [['siteId' => 'not*'], ['siteId' => ['not*']], false],
            [['siteId' => 'not*'], ['siteId' => 'not*'], true],
            [['siteId' => '*'], ['siteId' => '*'], true],
            [['relatedTo' => [1, 2, 3]], ['relatedTo' => [1, 2, 3]], true],
            [['relatedToAll' => [1, 2, 3]], ['relatedToAll' => [1, 2, 3]], false],
            [['relatedToAll' => [1, 2, 3]], ['relatedTo' => ['and', ['element' => 1], ['element' => 2], ['element' => 3]]], true],
        ];
    }
}
