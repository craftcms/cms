<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craftunit\helpers;

use Codeception\Test\Unit;
use craft\helpers\Path;
use UnitTester;


/**
 * Class PathHelperTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
class PathHelperTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @dataProvider isPathContainedData
     * @param $result
     * @param $input
     */
    public function testIsPathContained($result, $input)
    {
        $isContained = Path::ensurePathIsContained($input);
        $this->assertSame($result, $isContained);
    }
    public function isPathContainedData()
    {
        return [
            [true, '/'],
            [true, ''],
            [true, 'in/a/path'],
            [false, '../test'],
            [true, './test'],
            [true, 'test']
        ];
    }
}