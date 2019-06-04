<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\TestCase\Test;
use craft\helpers\Json;
use UnitTester;

/**
 * Unit tests for the Json Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class JsonHelperTest extends Test
{
    // Public Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     * @dataProvider jsonDecodableDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testDecodeIfJson($result, $input)
    {
        $this->assertSame($result, Json::decodeIfJson($input));
    }

    // Data Providers
    // =========================================================================

    public function jsonDecodableDataProvider(): array
    {
        $basicArray = [
            'WHAT DO WE WANT' => 'JSON',
            'WHEN DO WE WANT IT' => 'NOW',
        ];
        return [
            ['{"test":"test"', '{"test":"test"'],
            [$basicArray, json_encode($basicArray)],
            [null, '']
        ];
    }
}
