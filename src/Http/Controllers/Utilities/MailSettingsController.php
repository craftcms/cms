<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Utilities;

use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\SystemMessage\SystemMessages;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\MailSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function CraftCms\Cms\t;

final readonly class MailSettingsController
{
    use RespondsWithFlash;

    public function __construct(
        Utilities $utilitiesService,
    ) {
        if (! $utilitiesService->checkAuthorization(MailSettings::class)) {
            abort(403, 'User is not authorized to perform this action.');
        }
    }

    public function __invoke(Request $request, SystemMessages $systemMessages): Response
    {
        $data = $request->validate([
            'to' => ['required', 'email:strict'],
        ]);

        $to = $data['to'];

        $message = $systemMessages->mailable(
            key: 'test_email',
            user: new User(['username' => $to, 'email' => $to]),
            variables: [
                'settings' => MailSettings::settingsReport(),
            ])->to($to);

        try {
            Mail::sendNow($message);
        } catch (Throwable $e) {
            return back()->withErrors(['to' => $e->getMessage()]);
        }

        return $this->asSuccess(t('Email sent successfully! Check your inbox.'));
    }
}
