<?php

namespace myprojecttests;

use Craft;
use craft\test\TestCase;
use CraftCms\Cms\Edition;
use UnitTester;

class ExampleUnitTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     */
    public function testCraftEdition(): void
    {
        Craft::$app->setEdition(Edition::Pro);
        $this->assertSame(Edition::Pro, Craft::$app->edition);
    }
}
