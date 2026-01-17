<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\auth\methods\RecoveryCodes;
use craft\auth\methods\TOTP;
use craft\web\Controller;
use craft\web\View;
use CraftCms\Cms\Auth\Passkeys\Passkeys;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Translation\Locale;
use Illuminate\Support\Facades\Auth;
use Throwable;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\RangeNotSatisfiableHttpException;
use yii\web\Response;
use function CraftCms\Cms\t;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * AuthController handles various user authentication actions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class AuthController extends Controller
{
    /**
     * @inheritdoc
     */
    protected array|bool|int $allowAnonymous = [
        'passkey-request-options' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'verify-recovery-code' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'verify-totp' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
    ];

    /**
     * Returns the HTML for an authentication method’s setup slideout.
     *
     * @return Response
     */
    public function actionMethodSetupHtml(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $class = $this->request->getRequiredBodyParam('method');
        $method = Craft::$app->getAuth()->getMethod($class);
        $containerId = sprintf('auth-method-setup-%s', mt_rand());
        $displayName = $method::displayName();
        $view = Craft::$app->getView();
        $templateMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);

        try {
            $html = Html::tag('h1', t('{name} Setup', [
                    'name' => $displayName,
                ])) .
                $view->namespaceInputs(
                    fn() => $method->getSetupHtml($containerId),
                    $containerId,
                );
        } finally {
            $view->setTemplateMode($templateMode);
        }

        return $this->asJson([
            'containerId' => $containerId,
            'html' => $html,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
            'methodName' => $displayName,
        ]);
    }

    /**
     * Returns the HTML for an authentication method’s listing.
     *
     * @return Response
     */
    public function actionMethodListingHtml(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $view = Craft::$app->getView();
        $html = $view->renderTemplate('users/_auth-methods.twig', templateMode: View::TEMPLATE_MODE_CP);

        return $this->asJson([
            'html' => $html,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
        ]);
    }

    /**
     * Remove auth type setup (for 2FA or Passkeys) from the database
     *
     * @return Response|null
     * @throws Throwable
     * @throws BadRequestHttpException
     */
    public function actionRemoveMethod(): ?Response
    {
        $this->requireCpRequest();
        $this->requirePostRequest();
        $this->requireElevatedSession();

        $methodClass = $this->request->getRequiredBodyParam('method');

        $auth = Craft::$app->getAuth();
        $method = $auth->getMethod($methodClass);
        $method->remove();

        // if that was the last non-Recovery Codes method, remove Recovery Codes too
        if (empty($auth->getActiveMethods())) {
            $recoveryCodes = $auth->getMethod(RecoveryCodes::class);
            if ($recoveryCodes->isActive()) {
                $recoveryCodes->remove();
            }
        }

        return $this->asSuccess(t('Authentication method removed.'));
    }

    /**
     * Verifies a TOTP code.
     *
     * @return Response
     */
    public function actionVerifyTotp(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $code = $this->request->getRequiredBodyParam('code');
        $authService = Craft::$app->getAuth();

        if (!$authService->verify(TOTP::class, $code)) {
            return $this->asFailure($authService->getAuthErrorMessage());
        }

        return $this->asSuccess(t('Verification successful.'));
    }

    /**
     * Verifies a recovery code.
     *
     * @return Response
     */
    public function actionVerifyRecoveryCode(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $code = $this->request->getRequiredBodyParam('code');
        $authService = Craft::$app->getAuth();

        if (!$authService->verify(RecoveryCodes::class, $code)) {
            return $this->asFailure($authService->getAuthErrorMessage(t('Invalid recovery code.')));
        }

        return $this->asSuccess(t('Verification successful.'));
    }

    /**
     * Generates new passkey credential creation options for the user.
     *
     * @return Response
     */
    public function actionPasskeyCreationOptions(): Response
    {
        $this->requireCpRequest();
        $this->requireAcceptsJson();
        $this->requirePostRequest();
        $this->requireElevatedSession();

        $options = app(Passkeys::class)->getPasskeyCreationOptions(static::currentUser());

        return $this->asJson([
            'options' => $options,
        ]);
    }

    /**
     * Returns the available passkey options.
     *
     * @return Response
     */
    public function actionPasskeyRequestOptions(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $options = app(Passkeys::class)->getPasskeyRequestOptions();

        return $this->asJson([
            'options' => $options,
        ]);
    }

    /**
     * Verifies the new passkey credential creation.
     *
     * @return Response
     */
    public function actionVerifyPasskeyCreation(): Response
    {
        $this->requireCpRequest();
        $this->requireAcceptsJson();
        $this->requirePostRequest();
        $this->requireElevatedSession();

        $credentials = $this->request->getRequiredBodyParam('credentials');
        $credentialName = $this->request->getBodyParam('credentialName');

        $verified = app(Passkeys::class)->verifyPasskeyCreationResponse($credentials, $credentialName);

        if (!$verified) {
            return $this->asFailure(t('Passkey creation failed.'));
        }

        return $this->asSuccess(t('Passkey created.'), [
            'tableHtml' => $this->passkeyTableHtml(),
        ]);
    }

    /**
     * Deletes a passkey.
     *
     * @return Response
     */
    public function actionDeletePasskey(): Response
    {
        $this->requireCpRequest();
        $this->requireAcceptsJson();
        $this->requirePostRequest();

        $uid = $this->request->getRequiredBodyParam('uid');
        app(Passkeys::class)->deletePasskey(static::currentUser(), $uid);

        return $this->asSuccess(t('Passkey deleted.'), [
            'tableHtml' => $this->passkeyTableHtml(),
        ]);
    }

    private function passkeyTableHtml(): string
    {
        return $this->getView()->renderTemplate('users/_passkeys-table.twig', [
            'passkeys' => app(Passkeys::class)->getPasskeys(static::currentUser())->all(),
        ]);
    }

    /**
     * Generates new recovery codes.
     *
     * @return Response
     */
    public function actionGenerateRecoveryCodes(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();
        $this->requireElevatedSession();

        $recoveryCodes = Craft::$app->getAuth()->getMethod(RecoveryCodes::class);
        $codes = $recoveryCodes->generateRecoveryCodes();

        return $this->asSuccess(t('Recovery codes generated.'), [
            'codes' => $codes,
        ]);
    }

    /**
     * Downloads the user’s recovery codes as a text file.
     *
     * @return Response|null
     * @throws Throwable
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws HttpException
     * @throws RangeNotSatisfiableHttpException
     */
    public function actionDownloadRecoveryCodes(): ?Response
    {
        $this->requirePostRequest();
        $this->requireLogin();
        $this->requireElevatedSession();

        $recoveryCodes = Craft::$app->getAuth()->getMethod(RecoveryCodes::class);
        [$codes, $dateCreated] = $recoveryCodes->getRecoveryCodes();

        if (empty($codes)) {
            throw new InvalidConfigException('No recovery codes exist for this user.');
        }

        $systemName = t(Cms::systemName(), category: 'site');
        $systemNameUnderline = str_repeat('=', mb_strlen($systemName));
        $primarySite = Sites::getPrimarySite();
        $website = $primarySite->getBaseUrl() ?? $primarySite->getName();
        $user = Auth::user();
        $generalConfig = Cms::config();
        $username = !$generalConfig->useEmailAsUsername && $user->username ? $user->username : null;
        $account = $username ? sprintf('%s (%s)', $username, $user->email) : $user->email;
        $generated = I18N::getFormatter()->asDate($dateCreated, Locale::LENGTH_SHORT);
        $codeContent = implode('', array_map(
            fn(string $code) => $code ? "- $code\n" : "- ~~~~~~~~~~~~~\n",
            $codes,
        ));

        $content = <<<EOD
Recovery Codes for $systemName
===================$systemNameUnderline

These codes can be used as a backup form of verification, when you’re unable to
use your primary two-step verification method(s).

Each code can only be used once. Store them in a safe place!

Website:   $website
Account:   $account
Generated: $generated

$codeContent
EOD;

        $name = sprintf('%s recovery codes - %s.txt', $systemName, $username ?? $user->email);

        return $this->response->sendContentAsFile($content, $name, [
            'mimeType' => 'text/plain',
        ]);
    }
}
