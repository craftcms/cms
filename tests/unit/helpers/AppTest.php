<?php
namespace app\helpers;

use Craft;
use craft\helpers\App;

class AppTest extends \Codeception\TestCase\Test
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

    public function testEditions() {
        $this->assertEquals([Craft::Personal, Craft::Client, Craft::Pro], App::editions());
    }

    public function testNormalizeVersionNumber() {
        $this->assertEquals('1.2.3', App::normalizeVersionNumber('1.2-3'));
    }

    public function testEditionName() {
        $this->assertEquals('Personal', App::editionName(Craft::Personal));
        $this->assertEquals('Client', App::editionName(Craft::Client));
        $this->assertEquals('Pro', App::editionName(Craft::Pro));
    }


}
