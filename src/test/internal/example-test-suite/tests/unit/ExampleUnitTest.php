<?php

namespace myprojecttests;

use Codeception\Test\Unit;
use Craft;
use UnitTester;

class ExampleUnitTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     *
     */
    public function testCraftEdition()
    {
        Craft::$app->setEdition(Craft::Pro);

        $this->assertSame(
            Craft::Pro,
            Craft::$app->getEdition()
        );
    }
}
