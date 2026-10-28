<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Http;

use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use function CraftCms\Cms\template;

/**
 * The sign-in modal `Craft.AuthManager` shows when a session ends.
 */
class LoginModalController
{
    public function __invoke(Request $request, GeneralConfig $generalConfig, Impersonation $impersonation, HtmlStack $htmlStack): JsonResponse
    {
        $forElevatedSession = $request->boolean('forElevatedSession');

        // Whatever time is left is below the minimum, so clear it rather than
        // let the elevated session start partway through.
        if ($forElevatedSession) {
            $request->session()->forget($generalConfig->getPasswordConfirmationKey());
        }

        if ($forElevatedSession && ($impersonator = $impersonation->getImpersonator())) {
            $staticEmail = $impersonator->email;
        } else {
            $staticEmail = $request->validate(['email' => ['required']])['email'];
        }

        $html = template('yii2-adapter/_special/login-modal', [
            'action' => route('craft.cp.login.attempt'),
            'staticEmail' => $staticEmail,
            'forElevatedSession' => $forElevatedSession,
        ], templateMode: TemplateMode::Cp);

        return new JsonResponse([
            'html' => $html,
            'headHtml' => $htmlStack->headHtml(),
            'bodyHtml' => $htmlStack->bodyHtml(),
        ]);
    }
}
