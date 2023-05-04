<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\auth\Configurable2faInterface;
use craft\auth\type\RecoveryCodes;
use craft\auth\type\WebAuthn;
use craft\helpers\StringHelper;
use craft\web\Controller;
use craft\web\View;
use yii\base\Exception;
use yii\web\Response;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * The AuthController class is a controller that handles various 2FA-related actions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.0
 */
class AuthController extends Controller
{
    /**
     * @inheritdoc
     */
    protected array|bool|int $allowAnonymous = true;

    /**
     * Get all available alternative 2FA types for logging in.
     *
     * @return ?Response
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionFetchAlternative2faTypes(): ?Response
    {
        if (!$this->request->getIsPost()) {
            return null;
        }

        $authService = Craft::$app->getAuth();
        $auth2faData = $authService->get2faDataFromSession();
        $user = $auth2faData !== null ? $auth2faData['user'] : null;

        $currentMethod = Craft::$app->getRequest()->getBodyParam('currentMethod');
        $webAuthnSupported = Craft::$app->getRequest()->getBodyParam('webAuthnSupported');
        $alternativeTypes = Craft::$app->getAuth()->getAlternative2faTypes($currentMethod, $webAuthnSupported, $user);

        if ($this->request->getAcceptsJson()) {
            return $this->asSuccess(
                data: ['alternativeTypes' => $alternativeTypes],
            );
        }

        $template = Craft::$app->getRequest()->getBodyParam('template');

        return $this->renderTemplate($template, [
            'alternativeTypes' => $alternativeTypes,
        ], View::TEMPLATE_MODE_SITE);
    }

    /**
     * Return HTML for selected alternative 2FA type
     *
     * @return Response|null
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\base\Exception
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionLoadAlternative2faType(): ?Response
    {
        if (!$this->request->getIsPost()) {
            return null;
        }

        $selectedMethod = Craft::$app->getRequest()->getRequiredBodyParam('selectedMethod');
        if (empty($selectedMethod)) {
            return null;
        }

        $auth2faForm = (new $selectedMethod())->getInputHtml();

        if ($this->request->getAcceptsJson()) {
            if (empty($auth2faForm)) {
                return $this->asFailure(Craft::t('app', 'Something went wrong. Please start again.'));
            }

            return $this->asSuccess(
                data: ['auth2faForm' => $auth2faForm],
            );
        }

        $template = Craft::$app->getRequest()->getBodyParam('template');

        return $this->renderTemplate($template, [
            'auth2fa' => true,
            'auth2faForm' => $auth2faForm,
        ], View::TEMPLATE_MODE_SITE);
    }

    /**
     * Remove 2FA Type setup from the database
     *
     * @return Response|null
     * @throws \Throwable
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionRemoveSetup(): ?Response
    {
        if (!$this->request->getIsPost()) {
            return null;
        }

        $user = Craft::$app->getUser()->getIdentity();

        if ($user === null) {
            return null;
        }

        $currentMethod = Craft::$app->getRequest()->getRequiredBodyParam('currentMethod');
        if (empty($currentMethod)) {
            return null;
        }

        $success = (new $currentMethod())->removeSetup();

        if ($this->request->getAcceptsJson()) {
            if ($success) {
                return $this->asSuccess(Craft::t('app', 'Setup removed.'));
            } else {
                return $this->asFailure(Craft::t('app', 'Something went wrong.'));
            }
        }

        if ($success) {
            $this->setSuccessFlash(Craft::t('app', 'Setup removed.'));
        } else {
            $this->setFailFlash(Craft::t('app', 'Something went wrong.'));
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Save 2FA type setup
     *
     * @return Response|null
     * @throws Exception
     * @throws \Throwable
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionSaveSetup(): ?Response
    {
        if (!$this->request->getIsPost()) {
            return null;
        }

        $auth2faFields = Craft::$app->request->getRequiredBodyParam('auth2faFields');
        $currentMethod = Craft::$app->request->getRequiredBodyParam('currentMethod');

        if (empty($auth2faFields)) {
            return $this->asFailure(Craft::t('app', 'Please fill out the form.'));
        }

        if ($currentMethod === null) {
            return $this->asFailure(Craft::t('app', 'Something went wrong.'));
        }

        $authService = Craft::$app->getAuth();

        $verified = $authService->verify($auth2faFields, $currentMethod);

        if ($this->request->getAcceptsJson()) {
            if ($verified === false) {
                return $this->asFailure(Craft::t('app', 'Unable to verify.'));
            }

            return $this->asSuccess(Craft::t('app', 'Setup saved.'));
        }

        if ($verified === false) {
            $this->setFailFlash(Craft::t('app', 'Unable to verify.'));
        } else {
            $this->setSuccessFlash(Craft::t('app', 'Setup saved.'));
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Returns 2FA setup HTML for the slideout. Only triggered when editing your own account
     *
     * @return Response|null
     * @throws \Throwable
     */
    public function actionSetupSlideoutHtml(): ?Response
    {
        if (!$this->request->getIsPost() || !$this->request->getIsAjax()) {
            return null;
        }

        $user = Craft::$app->getUser()->getIdentity();

        if ($user === null) {
            return null;
        }

        $selectedMethod = Craft::$app->getRequest()->getRequiredBodyParam('selectedMethod');
        if (empty($selectedMethod)) {
            return null;
        }

        $auth2faType = new $selectedMethod();
        if (!($auth2faType instanceof Configurable2faInterface)) {
            throw new Exception('This 2FA type can’t be configured.');
        }

        $html = $auth2faType->getSetupFormHtml('',true, $user);

        return $this->asJson(['html' => $html]);
    }

    // WebAuthn methods
    ////////////////////////////////////////////////////////////////////////////

    /**
     * Generate & return the Public Key Credential Options for WebAuthn Registration
     *
     * @return Response
     * @throws \Throwable
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionGenerateRegistrationOptions(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();
        $this->requireLogin();
        $this->requireElevatedSession();

        $user = Craft::$app->getUser()->getIdentity();

        $webAuthn = new WebAuthn();
        $options = $webAuthn->getCredentialCreationOptions($user, true);

        return $this->asJson(['registrationOptions' => $options]);
    }

    /**
     * Verify the WebAuthn Registration Response and return the result + updated html markup for the slideout
     *
     * @return Response
     * @throws \Throwable
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionVerifyRegistration(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();
        $this->requireLogin();
        $this->requireElevatedSession();

        $user = Craft::$app->getUser()->getIdentity();
        $credentials = Craft::$app->getRequest()->getRequiredBodyParam('credentials');
        $credentialName = Craft::$app->getRequest()->getBodyParam('credentialName');

        $webAuthn = new WebAuthn();
        if ($webAuthn->verifyRegistrationResponse($user, $credentials, $credentialName)) {
            $data['verified'] = true;
            $data['html'] = $webAuthn->getSetupFormHtml('',true, $user);
        } else {
            $data['verified'] = false;
        }

        return $this->asJson($data);
    }

    /**
     * Process deleting a security key request
     *
     * @return Response
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionDeleteSecurityKey(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();
        $this->requireLogin();

        $user = Craft::$app->getUser()->getIdentity();
        $uid = Craft::$app->getRequest()->getRequiredBodyParam('uid');

        $webAuthn = new WebAuthn();
        if (!$webAuthn->deleteSecurityKey($user, $uid)) {
            return $this->asFailure(Craft::t('app', 'Something went wrong.'));
        }

        $data['html'] = $webAuthn->getSetupFormHtml('',true, $user);

        return $this->asSuccess(Craft::t('app', 'Security key removed.'), $data);
    }


    // Recovery Codes methods
    ////////////////////////////////////////////////////////////////////////////

    public function actionGenerateRecoveryCodes(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();
        $this->requireLogin();
        $this->requireElevatedSession();

        $user = Craft::$app->getUser()->getIdentity();

        $recoveryCodes = new RecoveryCodes();
        $codes = $recoveryCodes->generateRecoveryCodes($user);

        $data['verified'] = true;

        if (empty($codes)) {
            $data['verified'] = false;
        }
        $data['html'] = $recoveryCodes->getSetupFormHtml('',true, $user);

        return $this->asJson($data);
    }

    /**
     * Get user's recovery codes for download and return them as a file
     *
     * @return Response|null
     * @throws \Throwable
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\web\HttpException
     * @throws \yii\web\RangeNotSatisfiableHttpException
     */
    public function actionDownloadRecoveryCodes(): ?Response
    {
        $this->requirePostRequest();
        $this->requireLogin();
        $this->requireElevatedSession();

        $user = Craft::$app->getUser()->getIdentity();

        $recoveryCodes = new RecoveryCodes();
        $codes = $recoveryCodes->getRecoveryCodesForDownload($user->id);

        return $this->response->sendContentAsFile(
            $codes,
            StringHelper::toKebabCase(Craft::$app->getSystemName()) . '-recovery-codes.txt',
            ['mimeType' => 'text/plain']
        );
    }
}
