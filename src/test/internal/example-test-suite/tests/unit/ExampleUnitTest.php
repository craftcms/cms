<?php

namespace myprojecttests;

use Craft;
use craft\enums\CmsEdition;
use craft\test\TestCase;
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
        Craft::$app->setEdition(CmsEdition::Pro);
        $this->assertSame(CmsEdition::Pro, Craft::$app->edition);
    }
}
