<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\mail;

use craft\config\GeneralConfig as LegacyGeneralConfig;
use craft\test\TestCase;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Yii2Adapter\Mail\TestToEmailAddressCompatibility;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use ReflectionProperty;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class TestToEmailAddressCompatibilityTest extends TestCase
{
    private $oldConfig;

    protected function _before(): void
    {
        parent::_before();

        $this->oldConfig = Cms::config();

        $config = LegacyGeneralConfig::create();
        Config::set('craft.general', $config);
        app()->instance(GeneralConfig::class, $config);

        $reflection = new ReflectionProperty(Mail::mailer(), 'to');
        $reflection->setValue(Mail::mailer(), null);
    }

    protected function _after()
    {
        parent::_after();

        app()->instance(GeneralConfig::class, $this->oldConfig);
    }

    public function testReroutesOutgoingMailToConfiguredTestRecipients(): void
    {
        /** @var LegacyGeneralConfig $config */
        $config = app(GeneralConfig::class);
        $config->testToEmailAddress = [
            'safe@example.com' => 'Safe Recipient',
            'other@example.com' => 'Other Recipient',
        ];

        (new TestToEmailAddressCompatibility())->boot();

        $message = (new Email())
            ->to(new Address('real@example.com', 'Real Recipient'))
            ->cc(new Address('cc@example.com', 'Copied Recipient'))
            ->bcc(new Address('bcc@example.com', 'Blind Recipient'));

        event(new MessageSending($message));

        self::assertSame([
            'safe@example.com' => 'Safe Recipient',
            'other@example.com' => 'Other Recipient',
        ], collect($message->getTo())->mapWithKeys(
            fn(Address $address) => [$address->getAddress() => $address->getName()],
        )->all());
        self::assertSame([], $message->getCc());
        self::assertSame([], $message->getBcc());
    }

    public function testLeavesOutgoingMailRecipientsUntouchedWhenTestRecipientsAreNotConfigured(): void
    {
        (new TestToEmailAddressCompatibility())->boot();

        $message = (new Email())
            ->to(new Address('real@example.com', 'Real Recipient'))
            ->cc(new Address('cc@example.com', 'Copied Recipient'))
            ->bcc(new Address('bcc@example.com', 'Blind Recipient'));

        event(new MessageSending($message));

        self::assertSame([
            'real@example.com' => 'Real Recipient',
        ], collect($message->getTo())->mapWithKeys(
            fn(Address $address) => [$address->getAddress() => $address->getName()],
        )->all());
        self::assertSame([
            'cc@example.com' => 'Copied Recipient',
        ], collect($message->getCc())->mapWithKeys(
            fn(Address $address) => [$address->getAddress() => $address->getName()],
        )->all());
        self::assertSame([
            'bcc@example.com' => 'Blind Recipient',
        ], collect($message->getBcc())->mapWithKeys(
            fn(Address $address) => [$address->getAddress() => $address->getName()],
        )->all());
    }
}
