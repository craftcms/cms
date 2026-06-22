<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\mail;

use Craft;
use craft\config\GeneralConfig as LegacyGeneralConfig;
use craft\mail\Message;
use craft\test\TestCase;
use craft\test\TestMailer;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig as CmsGeneralConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Site\Exceptions\SiteNotFoundException;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\SystemMessage\Events\SystemMessagesResolving;
use CraftCms\Cms\SystemMessage\Models\SystemMessage;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use ReflectionException;
use UnitTester;
use yii\base\InvalidConfigException;

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

    public function testMessageProperties(): void
    {
        $this->markTestSkipped('TODO: Rework for Laravel with system messages');

        // app(ProjectConfig::class)->set('email', ['fromName' => '$FROM_EMAIL_NAME', 'fromEmail' => '$FROM_EMAIL_ADDRESS']);

        // Event::listen(SystemMessagesResolving::class, function(SystemMessagesResolving $event) {
        //     $event->messages = collect([
        //         new SystemMessage([
        //             'key' => 'account_activation',
        //             'body' => '{{fromEmail}} || {{fromName}}',
        //             'subject' => '{{fromName}} || {{fromEmail}}',
        //         ]),
        //     ]);
        // });

        // $this->_sendMail('test@craft.test');

        // /* @var Message $lastMessage */
        // $lastMessage = $this->tester->grabLastSentEmail();

        // self::assertSame('Craft CMS || info@craftcms.com', $lastMessage->getSubject());
        // self::assertStringContainsString('info@craftcms.com || Craft CMS', $lastMessage->toString());
    }

    /**
     *
     */
    public function testSendMessageCustomTemplate(): void
    {
        $this->_sendMail('test@craft.test');

        $lastMessage = $this->tester->grabLastSentEmail();
        self::assertStringContainsString('https://craftcms.com', $lastMessage->toString());
        self::assertStringContainsString('activate your account', $lastMessage->toString());
    }

    public function testSendMessageUsesConfiguredSystemMessageTemplate(): void
    {
        $originalTemplatesPath = Aliases::get('@templates');
        Aliases::set('@templates', dirname(__DIR__, 4) . '/tests/Support/templates');

        $projectConfig = app(ProjectConfig::class);
        $originalEmail = $projectConfig->get('email');
        $projectConfig->set('email', array_merge($originalEmail ?? [], [
            'template' => 'mail/custom-system-message.twig',
        ]));

        try {
            $this->_sendMail('test@craft.test');

            $lastMessage = $this->tester->grabLastSentEmail();
            $symfonyEmail = $lastMessage->getSymfonyEmail();

            self::assertStringContainsString('custom-system-message', (string)$symfonyEmail->getHtmlBody());
            self::assertStringContainsString('account_activation', (string)$symfonyEmail->getHtmlBody());
            self::assertStringContainsString('https://craftcms.com', (string)$symfonyEmail->getHtmlBody());
            self::assertSame('test@craft.test', array_key_first($lastMessage->to));
        } finally {
            if ($originalEmail === null) {
                $projectConfig->remove('email');
            } else {
                $projectConfig->set('email', $originalEmail);
            }
            Aliases::set('@templates', $originalTemplatesPath);
        }
    }

    public function testLegacyTemplatePropertyIsForwardedToNewSystem(): void
    {
        $originalTemplatesPath = Aliases::get('@templates');
        Aliases::set('@templates', dirname(__DIR__, 4) . '/tests/Support/templates');

        try {
            $this->mailer->template = 'mail/custom-system-message.twig';

            $this->_sendMail('test@craft.test');

            $lastMessage = $this->tester->grabLastSentEmail();
            $symfonyEmail = $lastMessage->getSymfonyEmail();

            self::assertStringContainsString('custom-system-message', (string)$symfonyEmail->getHtmlBody());
            self::assertStringContainsString('account_activation', (string)$symfonyEmail->getHtmlBody());
        } finally {
            $this->mailer->template = null;
            Aliases::set('@templates', $originalTemplatesPath);
        }
    }

    public function testLegacySiteOverridesTemplateIsForwardedToNewSystem(): void
    {
        $originalTemplatesPath = Aliases::get('@templates');
        Aliases::set('@templates', dirname(__DIR__, 4) . '/tests/Support/templates');
        $site = Sites::getPrimarySite();

        try {
            $this->mailer->siteOverrides = [
                $site->uid => [
                    'template' => 'mail/site-specific-message.twig',
                ],
            ];

            $user = Craft::$app->getUsers()->getUserById(1);
            $message = $this->mailer->composeFromKey('account_activation', [
                'user' => $user,
                'link' => 'https://craftcms.com',
                'name' => 'This is a name',
            ]);
            $message->setTo('test@craft.test');
            $message->siteId = $site->id;

            $this->mailer->send($message);

            $lastMessage = $this->tester->grabLastSentEmail();
            $symfonyEmail = $lastMessage->getSymfonyEmail();

            self::assertStringContainsString('site-specific-message', (string)$symfonyEmail->getHtmlBody());
            self::assertStringNotContainsString('custom-system-message', (string)$symfonyEmail->getHtmlBody());
        } finally {
            $this->mailer->siteOverrides = [];
            Aliases::set('@templates', $originalTemplatesPath);
        }
    }

    /**
     *
     */
    public function testToEmailAddress(): void
    {
        Cms::config()->testToEmailAddress = ['giel@yellowflash.net', 'info@craftcms.com'];

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
        Cms::config()->testToEmailAddress = ['giel@yellowflash.net' => 'Giel', 'info@craftcms.com' => 'Craft CMS'];

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

        Sites::getPrimarySite()->setLanguage('nl');
        app()->setLocale('en-US');

        $this->mailer->send($this->mailer->composeFromKey('account_activation', [
            'user' => new User(),
            'link' => 'https://craftcms.com',
        ]));

        self::assertSame($desiredLang, $this->tester->grabLastSentEmail()->language);

        Sites::getPrimarySite()->setLanguage('en-US');
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        parent::_before();

        /** @var CmsGeneralConfig $generalConfig */
        $generalConfig = app(CmsGeneralConfig::class);
        $legacyConfig = LegacyGeneralConfig::__set_state($generalConfig->toArray());

        Config::set('craft.general', $legacyConfig);
        app()->instance(CmsGeneralConfig::class, $legacyConfig);

        /** @var TestMailer $mailer */
        $mailer = Craft::$app->getMailer();
        $this->mailer = $mailer;
    }
}
