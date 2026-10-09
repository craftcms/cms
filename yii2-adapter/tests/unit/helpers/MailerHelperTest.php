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
use CraftCms\Cms\Deprecator\Deprecator;
use yii\base\Event;

class MailerHelperTest extends TestCase
{
    public function testRegisteredMailerTransportsAreIgnoredAndWarnedOnce(): void
    {
        /** @var Deprecator $deprecator */
        $deprecator = app(Deprecator::class);

        Event::on(MailerHelper::class, MailerHelper::EVENT_REGISTER_MAILER_TRANSPORTS, function(RegisterComponentTypesEvent $event) {
            $event->types[] = \stdClass::class;
        });

        $types = MailerHelper::allMailerTransportTypes();
        MailerHelper::allMailerTransportTypes();

        self::assertContains(Sendmail::class, $types);
        self::assertContains(Smtp::class, $types);
        self::assertNotContains(\stdClass::class, $types);

        $matches = array_filter(
            $deprecator->getRequestLogs(),
            fn($log) => $log->key === 'MailerHelper::EVENT_REGISTER_MAILER_TRANSPORTS',
        );

        self::assertCount(1, $matches);

        Event::off(MailerHelper::class, MailerHelper::EVENT_REGISTER_MAILER_TRANSPORTS);
    }

    public function testCreateTransportAdapterFailsLoudlyForUnsupportedTypes(): void
    {
        $this->expectException(MissingComponentException::class);
        $this->expectExceptionMessage('Configure a Laravel mailer/driver in your application config or environment instead.');

        MailerHelper::createTransportAdapter(\stdClass::class);
    }
}
