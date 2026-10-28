<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Auth;

use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Auth\OAuth\OAuth;
use CraftCms\Cms\Auth\SessionTimeout;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\View\HtmlFragment;
use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\TemplateGlobals;
use CraftCms\Cms\View\TemplateHooks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @since 6.0.0
 */
readonly class SessionInfoController
{
    use ConfirmsPasswords;

    public function show(Request $request, SessionTimeout $timeout): JsonResponse
    {
        $data = [
            'isGuest' => $request->craftUser() === null,
            'csrfTokenName' => '_token',
            'csrfTokenValue' => csrf_token(),
            'sessionExpiresAt' => $timeout->expiresAt($request)?->getTimestamp(),
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

    /**
     * Keeps the session alive. Any request that saves the session does, so this
     * only reports the new expiry.
     */
    public function renew(Request $request, SessionTimeout $timeout): JsonResponse
    {
        return new JsonResponse([
            'csrfTokenValue' => csrf_token(),
            'sessionExpiresAt' => $timeout->expiresAt($request)?->getTimestamp(),
        ]);
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

        $request->session()->forget($generalConfig->getPasswordConfirmationKey());
        $user = $impersonation->getImpersonator() ?? $request->craftUser()?->asElement();

        if (! $user) {
            abort(401);
        }

        $loginName = (string) ($user->email ?? $user->username);

        return new JsonResponse([
            'confirmed' => false,
            'timeout' => 0,
            'loginName' => $loginName,
            'alternativeLoginMethods' => $this->alternativeLoginMethods(
                $loginName,
                $generalConfig,
                $oauth,
                $htmlStack,
                $templateGlobals,
                $templateHooks,
            ),
        ]);
    }

    /**
     * The sign-in methods besides a password, for signing back in after the
     * session has expired.
     */
    public function loginMethods(
        Request $request,
        GeneralConfig $generalConfig,
        OAuth $oauth,
        HtmlStack $htmlStack,
        TemplateGlobals $templateGlobals,
        TemplateHooks $templateHooks,
    ): JsonResponse {
        $loginName = $request->validate([
            'loginName' => ['required', 'string'],
        ])['loginName'];

        return new JsonResponse([
            'alternativeLoginMethods' => $this->alternativeLoginMethods(
                $loginName,
                $generalConfig,
                $oauth,
                $htmlStack,
                $templateGlobals,
                $templateHooks,
            ),
        ]);
    }

    private function alternativeLoginMethods(
        string $loginName,
        GeneralConfig $generalConfig,
        OAuth $oauth,
        HtmlStack $htmlStack,
        TemplateGlobals $templateGlobals,
        TemplateHooks $templateHooks,
    ): ?HtmlFragment {
        $context = [
            ...$templateGlobals->resolve(),
            'action' => route('craft.cp.login.attempt'),
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

        return $alternativeLoginMethods->isEmpty() ? null : $alternativeLoginMethods;
    }
}
