<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Craft;
use craft\errors\ElementNotFoundException;
use craft\test\TestCase;
use CraftCms\Cms\ProjectConfig\Events\ItemUpdated;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Str;
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
        $configEvent = new ItemUpdated(
            path: 'globalSets.testUid',
            oldValue: [],
            newValue: [
                'name' => 'Test ' . Str::uuid()->toString(),
                'handle' => 'test' . Str::uuid()->toString(),
            ],
            tokenMatches: ['testuid'],
        );

        Elements::partialMock()->shouldReceive('saveElement')->andReturn(false);

        $this->tester->expectThrowable(ElementNotFoundException::class, function() use ($configEvent) {
            Craft::$app->getGlobals()->handleChangedGlobalSet($configEvent);
        });
    }
}
