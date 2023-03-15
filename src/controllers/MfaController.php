<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\mfa\ConfigurableMfaInterface;
use craft\mfa\type\WebAuthn;
use craft\web\Controller;
use craft\web\View;
use yii\base\Exception;
use yii\web\Response;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * The AuthenticationController class is a controller that handles various MFA-related actions.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.0
 */
class MfaController extends Controller
{
    /**
     * @inheritdoc
     */
    protected array|bool|int $allowAnonymous = true;

    /**
     * Get all available alternative MFA types for logging in.
     *
     * @return ?Response
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionFetchAlternativeMfaTypes(): ?Response
    {
        if (!$this->request->getIsPost()) {
            return null;
        }

        $currentMethod = Craft::$app->getRequest()->getBodyParam('currentMethod');
        $alternativeTypes = Craft::$app->getMfa()->getAlternativeMfaTypes($currentMethod);

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
     * Return HTML for selected alternative MFA type
     *
     * @return Response|null
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\base\Exception
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionLoadAlternativeMfaType(): ?Response
    {
        if (!$this->request->getIsPost()) {
            return null;
        }

        $selectedMethod = Craft::$app->getRequest()->getRequiredBodyParam('selectedMethod');
        if (empty($selectedMethod)) {
            return null;
        }

        $mfaForm = (new $selectedMethod())->getInputHtml();

        if ($this->request->getAcceptsJson()) {
            if (empty($mfaForm)) {
                return $this->asFailure(Craft::t('app', 'Something went wrong. Please start again.'));
            }

            return $this->asSuccess(
                data: ['mfaForm' => $mfaForm],
            );
        }

        $template = Craft::$app->getRequest()->getBodyParam('template');

        return $this->renderTemplate($template, [
            'mfa' => true,
            'mfaForm' => $mfaForm,
        ], View::TEMPLATE_MODE_SITE);
    }

    /**
     * Remove MFA Type setup from the database
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
     * Save MFA type setup
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

        $mfaFields = Craft::$app->request->getRequiredBodyParam('mfaFields');
        $currentMethod = Craft::$app->request->getRequiredBodyParam('currentMethod');

        if (empty($mfaFields)) {
            return $this->asFailure(Craft::t('app', 'Please fill out the form.'));
        }

        if ($currentMethod === null) {
            return $this->asFailure(Craft::t('app', 'Something went wrong.'));
        }

        $mfaService = Craft::$app->getMfa();

        $verified = $mfaService->verify($mfaFields, $currentMethod);

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
     * Returns MFA setup HTML for the slideout. Only triggered when editing your own account
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

        $mfaType = new $selectedMethod();
        if (!($mfaType instanceof ConfigurableMfaInterface)) {
            throw new Exception('This MFA type can’t be configured.');
        }

        $html = $mfaType->getSetupFormHtml('',true, $user);

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
            return $this->asFailure('Something went wrong.');
        }

        $data['html'] = $webAuthn->getSetupFormHtml('',true, $user);

        return $this->asSuccess('Security key removed.', $data);
    }
}
