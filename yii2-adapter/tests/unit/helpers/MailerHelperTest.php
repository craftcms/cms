<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use craft\events\RegisterComponentTypesEvent;
use craft\helpers\MailerHelper;
use craft\mail\transportadapters\Sendmail;
use craft\mail\transportadapters\Smtp;
use craft\test\TestCase;
use CraftCms\Cms\Component\Exceptions\MissingComponentException;
use crafttests\_support\LegacyUsageSpy;
use yii\base\Event;

class MailerHelperTest extends TestCase
{
    public function testRegisteredMailerTransportsAreIgnoredAndReported(): void
    {
        $usage = LegacyUsageSpy::bind();

        Event::on(MailerHelper::class, MailerHelper::EVENT_REGISTER_MAILER_TRANSPORTS, function(RegisterComponentTypesEvent $event) {
            $event->types[] = \stdClass::class;
        });

        try {
            $types = MailerHelper::allMailerTransportTypes();

            self::assertContains(Sendmail::class, $types);
            self::assertContains(Smtp::class, $types);
            self::assertNotContains(\stdClass::class, $types);
            self::assertContains('MailerHelper::EVENT_REGISTER_MAILER_TRANSPORTS', $usage->keys);
        } finally {
            Event::off(MailerHelper::class, MailerHelper::EVENT_REGISTER_MAILER_TRANSPORTS);
            LegacyUsageSpy::unbind();
        }
    }

    public function testCreateTransportAdapterFailsLoudlyForUnsupportedTypes(): void
    {
        $this->expectException(MissingComponentException::class);
        $this->expectExceptionMessage('Configure a Laravel mailer/driver in your application config or environment instead.');

        MailerHelper::createTransportAdapter(\stdClass::class);
    }
}
