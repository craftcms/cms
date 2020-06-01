<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use craft\helpers\Path;
use UnitTester;

/**
 * Class PathHelperTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class PathHelperTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @dataProvider isPathContainedDataProviders
     *
     * @param $result
     * @param $input
     */
    public function testIsPathContained($result, $input)
    {
        $isContained = Path::ensurePathIsContained($input);
        $this->assertSame($result, $isContained);
    }

    public function isPathContainedDataProviders(): array
    {
        return [
            [true, '/'],
            [true, ''],
            [true, 'in/a/path'],
            [false, '../test'],
            [true, './test'],
            [true, 'test'],
            [false, 'foo////../../bar'],
            [true, 'foo/0/0/0/../../bar'],
        ];
    }
}
