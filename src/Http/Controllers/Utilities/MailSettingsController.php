<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Utilities;

use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\SystemMessage\Actions\SendTestMailAction;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\MailSettings;
use Illuminate\Http\Request;
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

    public function __invoke(Request $request, SendTestMailAction $sendTestMail): Response
    {
        $data = $request->validate([
            'to' => ['required', 'email:strict'],
        ]);

        try {
            $sendTestMail->handle($data['to']);
        } catch (Throwable $e) {
            return back()->withErrors(['to' => $e->getMessage()]);
        }

        return $this->asSuccess(t('Email sent successfully! Check your inbox.'));
    }
}
