<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Mail;

use craft\config\GeneralConfig as LegacyGeneralConfig;
use CraftCms\Cms\Config\GeneralConfig;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class TestToEmailAddressCompatibility
{
    public function boot(): void
    {
        $testRecipients = $this->testRecipients();

        if (!empty($testRecipients)) {
            if (count($testRecipients) === 1) {
                $address = array_key_first($testRecipients);

                if ($address !== null) {
                    Mail::alwaysTo($address, $testRecipients[$address]);
                }
            }
        }

        Event::listen(MessageSending::class, function(MessageSending $event) {
            $testRecipients = $this->testRecipients();

            if (empty($testRecipients)) {
                return;
            }

            $this->overrideRecipients($event->message, $testRecipients);
        });
    }

    private function testRecipients(): array
    {
        $generalConfig = app(GeneralConfig::class);

        if (!$generalConfig instanceof LegacyGeneralConfig) {
            return [];
        }

        return $generalConfig->getTestToEmailAddress();
    }

    private function overrideRecipients(Email $message, array $testRecipients): void
    {
        $addresses = collect($testRecipients)
            ->map(fn(string $name, string $address) => new Address($address, $name))
            ->all();

        $message
            ->to(...$addresses)
            ->cc()
            ->bcc();
    }
}
