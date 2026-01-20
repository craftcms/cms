<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use Craft;
use craft\web\View;
use CraftCms\Cms\Auth\Auth;
use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use CraftCms\Cms\Auth\Methods\RecoveryCodes;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\Html;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

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
        $templateMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);

        try {
            $html = Html::tag('h1', t('{name} Setup', [
                'name' => $displayName,
            ])).
                $view->namespaceInputs(
                    fn () => $method->getSetupHtml($containerId),
                    $containerId,
                );
        } finally {
            $view->setTemplateMode($templateMode);
        }

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
        $html = $view->renderTemplate('users/_auth-methods.twig', templateMode: View::TEMPLATE_MODE_CP);

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
