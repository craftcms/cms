<?php
/**
 * Created by PhpStorm.
 * User: gieltettelaarlaptop
 * Date: 28/09/2018
 * Time: 11:05
 */

namespace craftunit\helpers;


use craft\helpers\Json;

/**
 * Unit tests for the Json Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class JsonHelperTest extends \Codeception\TestCase\Test
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

    public function testDecodeIfJson()
    {
        $jsonArray = [
            'WHAT DO WE WANT' => 'JSON',
            'WHEN DO WE WANT IT' => "NOW",
        ];

        $json = json_encode($jsonArray);
        $this->assertSame(
            Json::decodeIfJson(
                $json
            ),
            [
                'WHAT DO WE WANT' => 'JSON',
                'WHEN DO WE WANT IT' => "NOW",
            ]
        );

        // Empty string should be valid json.
        $this->assertSame(null, Json::decodeIfJson(''));

        // Invalid json should return string.
        $this->assertSame('{"test":"test"', Json::decodeIfJson('{"test":"test"'));
    }
}