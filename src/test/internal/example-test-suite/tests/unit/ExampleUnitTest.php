<?php

namespace myprojecttests;

use Craft;
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
        Craft::$app->setEdition(Craft::Pro);

        $this->assertSame(
            Craft::Pro,
            Craft::$app->getEdition()
        );
    }
}
