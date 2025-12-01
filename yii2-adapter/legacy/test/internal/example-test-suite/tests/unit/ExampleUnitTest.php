<?php

namespace myprojecttests;

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
        Edition::set(Edition::Pro);
        $this->assertSame(Edition::Pro, Edition::get());
    }
}
