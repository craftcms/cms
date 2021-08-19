<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Codeception\Stub\Expected;
use Craft;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\services\ProjectConfig;
use craft\services\Sections;
use craft\test\TestCase;
use UnitTester;
use yii\base\NotSupportedException;

/**
 * Unit tests for ProjectConfig service.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ProjectConfigTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * Test if rebuilding project config ignores the `readOnly` flag.
     */
    public function testRebuildIgnoresReadOnly()
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $readOnly = $projectConfig->readOnly;
        $projectConfig->readOnly = true;

        $failToSet = function() use ($projectConfig) {
            $projectConfig->set('oops', true);
        };

        // Must trigger exception
        $this->tester->expectThrowable(NotSupportedException::class, $failToSet);

        // Must not trigger exception
        $projectConfig->rebuild();

        $projectConfig->readOnly = $readOnly;
    }

    /**
     * @param $incomingData
     * @param $expectedResult
     * @dataProvider encodeTestDataProvider
     */
    public function testEncodeData($incomingData, $expectedResult)
    {
        $projectConfig = Craft::$app->getProjectConfig();
        self::assertSame($expectedResult, $this->invokeMethod($projectConfig, 'encodeValueAsString', [$incomingData]));
    }

    public function getConfigProvider()
    {
        return [
            [
                ['a' => 'b'],
                ['b' => 'c'],
                ['c' => 'a'],
                true,
                [
                    'a' => null,
                    'b' => 'c',
                    'c' => null,
                ]
            ],
            [
                ['a' => 'b'],
                null,
                ['c' => 'a'],
                true,
                [
                    'a' => 'b',
                    'b' => null,
                    'c' => null,
                ]
            ],
            [
                ['a' => 'b'],
                ['b' => 'c'],
                ['c' => 'a'],
                false,
                [
                    'a' => null,
                    'b' => null,
                    'c' => 'a',
                ]
            ],
        ];
    }

    public function setConfigProvider()
    {
        return [
            [
                'a.b.c',
                ['foo' => 'bar']
            ],
            [
                'a.b',
                ['foo' => 'bar', 'bar' => ['baz']]
            ]
        ];
    }

    public function encodeTestDataProvider()
    {
        return [
            [
                'foo',
                '"foo"'
            ],
            [
                true,
                'true'
            ],
            [
                null,
                'null'
            ],
            [
                false,
                'false'
            ],
            [
                2.5,
                '2.5'
            ],
            [
                0,
                '0'
            ],
            [
                2,
                '2'
            ],
            [
                2.0,
                '2.0'
            ],
        ];
    }
}
