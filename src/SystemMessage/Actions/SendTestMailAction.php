<?php

declare(strict_types=1);

namespace CraftCms\Cms\SystemMessage\Actions;

use CraftCms\Cms\SystemMessage\SystemMessages;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Utility\Utilities\MailSettings;
use Illuminate\Support\Facades\Mail;

final readonly class SendTestMailAction
{
    public function __construct(
        private SystemMessages $systemMessages
    ) {}

    public function handle(string $to): void
    {
        $message = $this->systemMessages->mailable(
            key: 'test_email',
            user: new User(['username' => $to, 'email' => $to]),
            variables: [
                'settings' => MailSettings::settingsReport(),
            ])->to($to);

        Mail::sendNow($message);
    }
}
