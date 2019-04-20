<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */
namespace craftunit\mail;

use Codeception\Test\Unit;
use craft\helpers\App;
use craft\mail\Mailer;
use craft\mail\Message;

/**
 * Unit tests for MailerTest
 *
 * TODO: Are we going to override the yii\mail\BaseMailer\TestMailer class in the Craft CMS module. So that we can
 * mock Craft::$app->getMailer()->send();
 *
 * Currently getMailer returns the TestMailer class (See line 264 of Codeception\Lib\Connector\Yii2) and not a craft\mail\Mailer object.
 * We need a way to test lines 89-167 of the craft\mail\Mailer object which is currently awkward.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class MailerTest extends Unit
{
    public $mailer;

    public function _before()
    {
        parent::_before();
        $this->mailer = \Craft::createObject(App::mailerConfig());
    }

    /**
     * Tests mail from key composition
     * @dataProvider fromKeyComposition
     */
    public function testFromKeyComposition($key, array $variables = [])
    {
        $res = $this->mailer->composeFromKey($key, $variables);
        $this->assertInstanceOf(Message::class, $res);
        $this->assertSame($key, $res->key);
        $this->assertSame($variables, $res->variables);
    }
    public function fromKeyComposition()
    {
        return[
            ['account_activation', []],
            ['not_a_key that exists']
        ];
    }
}