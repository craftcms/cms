<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\mail;

use Craft;
use craft\elements\User;
use craft\errors\SiteNotFoundException;
use craft\helpers\ArrayHelper;
use craft\mail\Message;
use craft\models\SystemMessage;
use craft\test\TestCase;
use craft\test\TestMailer;
use ReflectionException;
use UnitTester;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\web\ServerErrorHttpException;

/**
 * Unit tests for MailerTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class MailerTest extends TestCase
{
    // Public Properties
    // =========================================================================

    /**
     * @var TestMailer
     */
    public $mailer;

    /**
     * @var UnitTester
     */
    protected $tester;

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

    /**
     *
     */
    public function testSendMail()
    {
        $this->_sendMail();
        $this->assertInstanceOf(Message::class, $this->tester->grabLastSentEmail());
    }

    /**
     * @throws InvalidConfigException
     * @throws SiteNotFoundException
     * @throws ReflectionException
     */
    public function testSendMailLanguageDetermination()
    {
        $this->_testSendMailLanguage(true, 'nl');
        $this->_testSendMailLanguage(false, 'en-US');
    }

    /**
     *
     */
    public function testDefaultFrom()
    {
        $this->mailer->from = 'info@craftcms.com';

        $this->_sendMail();

        $this->assertSame(
            $this->mailer->from,
            ArrayHelper::firstKey($this->tester->grabLastSentEmail()->getFrom())
        );
    }

    /**
     *
     */
    public function testEmailVariables()
    {
        $this->_sendMail();

        $variables = $this->tester->grabLastSentEmail()->variables;

        $this->assertSame('1', (string)$variables['user']->id);
        $this->assertSame('https://craftcms.com', $variables['link']);
    }

    /**
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
    public function testMessageProperties()
    {
        Craft::$app->getProjectConfig()->set('email', ['fromName' => '$FROM_EMAIL_NAME', 'fromEmail' => '$FROM_EMAIL_ADDRESS']);
        $this->tester->mockCraftMethods('systemMessages', [
            'getMessage' => new SystemMessage([
                'body' => '{{fromEmail}} || {{fromName}}',
                'subject' => '{{fromName}} || {{fromEmail}}',
            ])
        ]);

        $this->_sendMail();

        /* @var Message $lastMessage */
        $lastMessage = $this->tester->grabLastSentEmail();

        $this->assertSame('Craft CMS || info@craftcms.com', $lastMessage->getSubject());
        $this->assertStringContainsString('info@craftcms.com || Craft CMS', $lastMessage->swiftMessage->toString());
    }

    /**
     *
     */
    public function testSendMessageCustomTemplate()
    {
        // Only works for rich peeps.
        Craft::$app->setEdition(Craft::Pro);
        $this->mailer->template = 'withvar';

        $this->_sendMail();

        $lastMessage = $this->tester->grabLastSentEmail();
        $this->assertStringContainsString('Hello iam This is a name', $lastMessage->swiftMessage->toString());
    }

    /**
     *
     */
    public function testToEmailAddress()
    {
        Craft::$app->getConfig()->getGeneral()->testToEmailAddress = ['giel@yellowflash.net', 'info@craftcms.com'];

        $this->_sendMail();
        $lastMessage = $this->tester->grabLastSentEmail();

        $this->assertSame([
            'giel@yellowflash.net' => 'Test Recipient',
            'info@craftcms.com' => 'Test Recipient'
        ], $lastMessage->to);
    }

    /**
     *
     */
    public function testToEmailAddressWithCustomName()
    {
        Craft::$app->getConfig()->getGeneral()->testToEmailAddress = ['giel@yellowflash.net' => 'Giel', 'info@craftcms.com' => 'Craft CMS'];

        $this->_sendMail();
        $lastMessage = $this->tester->grabLastSentEmail();

        $this->assertSame([
            'giel@yellowflash.net' => 'Giel',
            'info@craftcms.com' => 'Craft CMS'
        ], $lastMessage->to);
    }

    // Data Providers
    // =========================================================================

    /**
     * @return array
     */
    public function fromKeyCompositionDataProvider(): array
    {
        return [
            ['account_activation', []],
            ['not_a_key that exists']
        ];
    }

    // Protected Methods
    // =========================================================================

    protected function _sendMail()
    {
        $user = Craft::$app->getUsers()->getUserById('1');
        $this->mailer->send($this->mailer->composeFromKey('account_activation', [
            'user' => $user,
            'link' => 'https://craftcms.com',
            'name' => 'This is a name'
        ]));
    }

    /**
     * @param bool $isCpRequest
     * @param string $desiredLang
     * @throws InvalidConfigException
     * @throws SiteNotFoundException
     * @throws ReflectionException
     */
    protected function _testSendMailLanguage(bool $isCpRequest, string $desiredLang)
    {
        $this->setInaccessibleProperty(Craft::$app->getRequest(), '_isCpRequest', $isCpRequest);

        Craft::$app->getSites()->getPrimarySite()->language = 'nl';
        Craft::$app->language = 'en-US';

        $this->mailer->send($this->mailer->composeFromKey('account_activation', [
            'user' => new User(),
            'link' => 'https://craftcms.com'
        ]));

        $this->assertSame($desiredLang, $this->tester->grabLastSentEmail()->language);
    }

    /**
     * @inheritdoc
     */
    protected function _before()
    {
        parent::_before();

        $this->mailer = Craft::$app->getMailer();
    }
}
