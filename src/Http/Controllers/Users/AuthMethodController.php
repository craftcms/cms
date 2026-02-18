<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use Craft;
use CraftCms\Cms\Auth\Auth;
use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use CraftCms\Cms\Auth\Methods\RecoveryCodes;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

final readonly class AuthMethodController
{
    use ConfirmsPasswords;
    use RespondsWithFlash;

    public function __construct(
        private Auth $auth,
    ) {}

    public function setupHtml(Request $request): JsonResponse
    {
        $class = $request->validate([
            'method' => ['required', 'string'],
        ])['method'];

        $method = $this->auth->getMethod($class);
        $containerId = sprintf('auth-method-setup-%s', mt_rand());
        $displayName = $method::displayName();

        $view = Craft::$app->getView();
        $html = TemplateMode::with(
            TemplateMode::Cp,
            fn () => Html::tag('h1', t('{name} Setup', [
                'name' => $displayName,
            ])).InputNamespace::namespaceInputs(
                fn () => $method->getSetupHtml($containerId),
                $containerId,
            )
        );

        return new JsonResponse([
            'containerId' => $containerId,
            'html' => $html,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
            'methodName' => $displayName,
        ]);
    }

    public function listingHtml(): JsonResponse
    {
        $view = Craft::$app->getView();
        $html = template('users/_auth-methods', templateMode: TemplateMode::Cp);

        return new JsonResponse([
            'html' => $html,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
        ]);
    }

    public function destroy(Request $request): Response
    {
        $this->requireConfirmedPassword('An elevated session is required to remove an authentication method.');

        $methodClass = $request->validate([
            'method' => ['required', 'string'],
        ])['method'];

        $method = $this->auth->getMethod($methodClass);
        $method->remove();

        // if that was the last non-Recovery Codes method, remove Recovery Codes too
        if ($this->auth->getActiveMethods()->isEmpty()) {
            $recoveryCodes = $this->auth->getMethod(RecoveryCodes::class);
            if ($recoveryCodes->isActive()) {
                $recoveryCodes->remove();
            }
        }

        return $this->asSuccess(t('Authentication method removed.'));
    }
}
