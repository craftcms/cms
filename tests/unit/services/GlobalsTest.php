<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Codeception\Test\Unit;
use Craft;
use craft\errors\ElementNotFoundException;
use craft\events\ConfigEvent;
use craft\helpers\StringHelper;
use craft\test\TestCase;
use UnitTester;

/**
 * Unit tests for Globals service.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.16
 */
class GlobalsTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    protected function _before(): void
    {
    }

    // @TODO: more tests, obviously.

    /**
     * Test if rebuilding project congif ignores the `readOnly` flag.
     */
    public function testAbortOnUnsavedElement(): void
    {
        $configEvent = new ConfigEvent([
            'path' => 'globalSets.testUid',
            'tokenMatches' => ['testuid'],
            'oldValue' => [],
            'newValue' => [
                'name' => 'Test ' . StringHelper::UUID(),
                'handle' => 'test' . StringHelper::UUID(),
            ],
        ]);

        $this->tester->mockMethods(Craft::$app, 'elements', ['saveElement' => false]);

        $this->tester->expectThrowable(ElementNotFoundException::class, function() use ($configEvent) {
            Craft::$app->getGlobals()->handleChangedGlobalSet($configEvent);
        });
    }
}
