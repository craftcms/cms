<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\mail;

use Codeception\Test\Unit;
use Craft;
use craft\helpers\App;
use craft\mail\Mailer;
use craft\mail\Message;
use craft\mail\transportadapters\Sendmail;
use craft\models\MailSettings;
use yii\base\InvalidConfigException;

/**
 * Unit tests for MailerTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.1
 */
class MailerTest extends Unit
{
    // Public Properties
    // =========================================================================

    /**
     * @var Mailer $mailer
     */
    public $mailer;

    // Public Methods
    // =========================================================================

    // Tests Methods
    // =========================================================================

    /**
     * Tests mail from key composition
     *
     * @dataProvider fromKeyCompositionDataProvider
     *
     * @param $key
     * @param array $variables
     * @throws InvalidConfigException
     */
    public function testFromKeyComposition($key, array $variables = [])
    {
        $res = $this->mailer->composeFromKey($key, $variables);
        $this->assertInstanceOf(Message::class, $res);
        $this->assertSame($key, $res->key);
        $this->assertSame($variables, $res->variables);
    }

    // Data Providers
    // =========================================================================

    /**
     * @return array
     */
    public function fromKeyCompositionDataProvider(): array
    {
        return[
            ['account_activation', []],
            ['not_a_key that exists']
        ];
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function _before()
    {
        parent::_before();
        
        $mailSettings = new MailSettings([
            'transportType' => Sendmail::class
        ]);

        $this->mailer = Craft::createObject(App::mailerConfig(
            $mailSettings
        ));
    }
}
