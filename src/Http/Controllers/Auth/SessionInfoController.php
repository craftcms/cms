<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Auth;

use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\TemplateGlobals;
use CraftCms\Cms\View\TemplateHooks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

readonly class SessionInfoController
{
    use ConfirmsPasswords;

    public function show(Request $request): JsonResponse
    {
        $data = [
            'isGuest' => $request->craftUser() === null,
            'csrfTokenName' => '_token',
            'csrfTokenValue' => csrf_token(),
        ];

        if ($user = $request->craftUser()) {
            $userElement = $user->asElement();
            $data['id'] = $user->getCraftUserId();
            $data['uid'] = $userElement->uid;
            $data['username'] = $userElement->username;
            $data['email'] = $userElement->email;
        }

        return new JsonResponse($data);
    }

    public function confirmTimeout(): JsonResponse
    {
        return new JsonResponse([
            'timeout' => $this->confirmedPasswordTimeout(),
        ]);
    }

    public function confirmPassword(
        Request $request,
        GeneralConfig $generalConfig,
        Impersonation $impersonation,
        OAuth $oauth,
        HtmlStack $htmlStack,
        TemplateGlobals $templateGlobals,
        TemplateHooks $templateHooks,
    ): JsonResponse {
        $validated = $request->validate([
            'minimumRemainingSeconds' => ['sometimes', 'integer', 'min:0'],
            'force' => ['sometimes', 'boolean'],
        ]);
        $minimumRemainingSeconds = $validated['minimumRemainingSeconds'] ?? 5;
        $force = $validated['force'] ?? false;
        $timeout = $this->confirmedPasswordTimeout();

        if ($timeout === false) {
            return new JsonResponse([
                'confirmed' => true,
                'timeout' => false,
            ]);
        }

        if (! $force && $timeout !== 0 && $timeout >= $minimumRemainingSeconds) {
            return new JsonResponse([
                'confirmed' => true,
                'timeout' => $timeout,
            ]);
        }

        $request->session()->forget('auth.password_confirmed_at');
        $user = $impersonation->getImpersonator() ?? $request->craftUser()?->asElement();

        if (! $user) {
            abort(401);
        }

        $loginName = (string) ($user->email ?? $user->username);
        $context = [
            ...$templateGlobals->resolve(),
            'action' => action([LoginController::class, 'attemptLogin']),
            'forElevatedSession' => true,
            'generalConfig' => $generalConfig,
            'staticEmail' => $loginName,
            'username' => $loginName,
        ];
        $alternativeLoginMethods = $htmlStack->capture(
            fn (): string => implode('', [
                ...$oauth->getLoginButtons(true),
                $templateHooks->invoke('cp.login.alternative-login-methods', $context),
            ]),
        );

        return new JsonResponse([
            'confirmed' => false,
            'timeout' => 0,
            'loginName' => $loginName,
            'alternativeLoginMethods' => $alternativeLoginMethods->isEmpty() ? null : $alternativeLoginMethods,
        ]);
    }
}
