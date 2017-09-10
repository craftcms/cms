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

    public function testEditions()
    {
        $this->assertEquals([Craft::Personal, Craft::Client, Craft::Pro], App::editions());
    }

    public function testNormalizeVersionNumber()
    {
        $this->assertEquals('1.2.3', App::normalizeVersionNumber('1.2-3'));
    }

    public function testEditionName()
    {
        $this->assertEquals('Personal', App::editionName(Craft::Personal));
        $this->assertEquals('Client', App::editionName(Craft::Client));
        $this->assertEquals('Pro', App::editionName(Craft::Pro));
    }

    public function testIsValidEdition()
    {
        $this->assertTrue(App::isValidEdition('1'));
        $this->assertFalse(App::isValidEdition(null));
        $this->assertFalse(App::isValidEdition(false));
        $this->assertTrue(App::isValidEdition(0));
        $this->assertFalse(App::isValidEdition(4));
        $this->assertTrue(App::isValidEdition(1));
        $this->assertTrue(App::isValidEdition(2));
        $this->assertFalse(App::isValidEdition(3));
    }

    public function testMajorVersion()
    {
        $this->assertEquals('2', App::majorVersion('2'));
        $this->assertEquals('2', App::majorVersion('2.0'));
        $this->assertEquals('2', App::majorVersion('2.0.1'));
        $this->assertEquals('2', App::majorVersion('2.0-dev'));
    }

    public function testMajorMinorVersion()
    {
        $this->assertEquals(null, App::majorMinorVersion('2'));
        $this->assertEquals('2.0', App::majorMinorVersion('2.0'));
        $this->assertEquals('2.0', App::majorMinorVersion('2.0.1'));
        $this->assertEquals('2.0', App::majorMinorVersion('2.0-dev'));
    }

    public function testCraftDownloadUrl()
    {
        $this->assertEquals('https://download.craftcdn.com/craft/3.0/3.0.1234/Craft-3.0.1234.zip', App::craftDownloadUrl('3.0.1234'));
    }
}
