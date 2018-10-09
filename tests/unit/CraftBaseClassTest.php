<?php
namespace craftunit\base;

use Craft;
use craft\db\Query;
use craft\elements\User;
use craft\helpers\App;
use craft\helpers\UrlHelper;
use craft\mail\Message;
use craft\web\Application;
use craft\web\Request;
use craftunit\fixtures\TestFixture;

class CraftBaseClassTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    public function _fixtures()
    {
        return [
            'test' => [
                'class' => TestFixture::class,
            ]
        ];
    }

    public function testFixtureIsWorking()
    {
        // This isnt a fully specified test. Instead we just check that *a* record exists.
        $query = new Query();
        $demoUser = $query->select('*')->from('{{%users}}')->one();
        $this->assertSame('craftcms', $demoUser['username']);
        $this->assertSame('craft@cms.com', $demoUser['email']);
    }

}