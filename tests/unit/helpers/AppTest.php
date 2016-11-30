<?php
namespace app\helpers;

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

    public function testEditions()
    {
        $this->assertEquals([0,1,2], App::editions());
    }

    public function testNormalizeVersionNumber()
    {
        $this->assertEquals('1.2.3', App::normalizeVersionNumber('1.2-3'));
    }

    public function testEditionName()
    {
        $this->assertEquals('Personal', App::editionName(0));
        $this->assertEquals('Client', App::editionName(1));
        $this->assertEquals('Pro', App::editionName(2));
    }
}