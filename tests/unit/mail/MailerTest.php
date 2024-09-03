<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\mail;

use Craft;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\errors\SiteNotFoundException;
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
    /**
     * @var TestMailer
     */
    public TestMailer $mailer;

    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * Tests mail from key composition
     *
     * @dataProvider fromKeyCompositionDataProvider
     * @param string $key
     * @param array $variables
     * @throws InvalidConfigException
     */
    public function testFromKeyComposition(string $key, array $variables = []): void
    {
        $res = $this->mailer->composeFromKey($key, $variables);
        self::assertInstanceOf(Message::class, $res);
        self::assertSame($key, $res->key);
        self::assertSame($variables, $res->variables);
    }

    /**
     *
     */
    public function testSendMail(): void
    {
        $this->_sendMail();
        self::assertInstanceOf(Message::class, $this->tester->grabLastSentEmail());
    }

    /**
     * @throws InvalidConfigException
     * @throws SiteNotFoundException
     * @throws ReflectionException
     */
    public function testSendMailLanguageDetermination(): void
    {
        $this->_testSendMailLanguage(true, 'nl');
        $this->_testSendMailLanguage(false, 'en-US');
    }

    /**
     *
     */
    public function testDefaultFrom(): void
    {
        $this->mailer->from = 'info@craftcms.com';

        $this->_sendMail();

        self::assertSame(
            $this->mailer->from,
            array_key_first($this->tester->grabLastSentEmail()->getFrom())
        );
    }

    /**
     *
     */
    public function testEmailVariables(): void
    {
        $this->_sendMail();

        $variables = $this->tester->grabLastSentEmail()->variables;

        self::assertSame('1', (string)$variables['user']->id);
        self::assertSame('https://craftcms.com', $variables['link']);
    }

    /**
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
    public function testMessageProperties(): void
    {
        Craft::$app->getProjectConfig()->set('email', ['fromName' => '$FROM_EMAIL_NAME', 'fromEmail' => '$FROM_EMAIL_ADDRESS']);
        $this->tester->mockCraftMethods('systemMessages', [
            'getMessage' => new SystemMessage([
                'body' => '{{fromEmail}} || {{fromName}}',
                'subject' => '{{fromName}} || {{fromEmail}}',
            ]),
        ]);

        $this->_sendMail('test@craft.test');

        /* @var Message $lastMessage */
        $lastMessage = $this->tester->grabLastSentEmail();

        self::assertSame('Craft CMS || info@craftcms.com', $lastMessage->getSubject());
        self::assertStringContainsString('info@craftcms.com || Craft CMS', $lastMessage->toString());
    }

    /**
     *
     */
    public function testSendMessageCustomTemplate(): void
    {
        Craft::$app->edition = CmsEdition::Pro;
        $this->mailer->template = 'withvar';

        $this->_sendMail('test@craft.test');

        $lastMessage = $this->tester->grabLastSentEmail();
        self::assertStringContainsString('Hello iam This is a name', $lastMessage->toString());
    }

    /**
     *
     */
    public function testToEmailAddress(): void
    {
        Craft::$app->getConfig()->getGeneral()->testToEmailAddress = ['giel@yellowflash.net', 'info@craftcms.com'];

        $this->_sendMail();
        $lastMessage = $this->tester->grabLastSentEmail();

        self::assertSame([
            'giel@yellowflash.net' => 'Test Recipient',
            'info@craftcms.com' => 'Test Recipient',
        ], $lastMessage->to);
    }

    /**
     *
     */
    public function testToEmailAddressWithCustomName(): void
    {
        Craft::$app->getConfig()->getGeneral()->testToEmailAddress = ['giel@yellowflash.net' => 'Giel', 'info@craftcms.com' => 'Craft CMS'];

        $this->_sendMail();
        $lastMessage = $this->tester->grabLastSentEmail();

        self::assertSame([
            'giel@yellowflash.net' => 'Giel',
            'info@craftcms.com' => 'Craft CMS',
        ], $lastMessage->to);
    }

    /**
     * @return array
     */
    public static function fromKeyCompositionDataProvider(): array
    {
        return [
            ['account_activation', []],
            ['not_a_key that exists'],
        ];
    }

    protected function _sendMail(?string $to = null)
    {
        $user = Craft::$app->getUsers()->getUserById(1);
        $message = $this->mailer->composeFromKey('account_activation', [
            'user' => $user,
            'link' => 'https://craftcms.com',
            'name' => 'This is a name',
        ]);

        if ($to) {
            $message->setTo($to);
        }

        $this->mailer->send($message);
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
            'link' => 'https://craftcms.com',
        ]));

        self::assertSame($desiredLang, $this->tester->grabLastSentEmail()->language);
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        parent::_before();

        /** @var TestMailer $mailer */
        $mailer = Craft::$app->getMailer();
        $this->mailer = $mailer;
    }
}
