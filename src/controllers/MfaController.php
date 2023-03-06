<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\mfa\ConfigurableMfaInterface;
use craft\web\Controller;
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
     * @return Response|null
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionGetAlternativeMfaTypes(): ?Response
    {
        if (!$this->request->getIsPost()) {
            return null;
        }

        $currentMethod = Craft::$app->getRequest()->getRequiredBodyParam('currentMethod');
        $alternativeTypes = Craft::$app->getMfa()->getAlternativeMfaTypes($currentMethod);

        if ($this->request->getAcceptsJson()) {
            return $this->asSuccess(
                data: ['alternativeTypes' => $alternativeTypes],
            );
        }

        // todo: finish me
        return null;
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
                return $this->asFailure('Something went wrong. Please start again.');
            }

            return $this->asSuccess(
                data: ['mfaForm' => $mfaForm],
            );
        }

        // todo: finish me
        return null;
    }

    public function actionRemoveSetup(): ?Response
    {
        if (!$this->request->getIsPost()) {
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

        $success = (new $selectedMethod())->removeSetup();

        if ($this->request->getAcceptsJson()) {
            if ($success) {
                return $this->asSuccess('removal done');
            } else {
                return $this->asFailure('removal not done');
            }
        }

        return null; // todo: finish me
    }

    public function actionSaveSetup(): ?Response
    {
        return $this->asSuccess('all good');
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
            throw new Exception('asd');
        }

        $html = $mfaType->getSetupFormHtml('',true, $user);

        return $this->asJson(['html' => $html]);
    }

    //    public function actionGetQrCode(): Response
//    {
//
//    }

//    public function actionVerify(): Response
//    {
//        $verificationCode = Craft::$app->request->getRequiredBodyParam('verificationCode');
//        if (empty($verificationCode)) {
//            return $this->asFailure('Please provide a verification code');
//        }
//
//        $authenticationService = Craft::$app->getMfa();
//        $mfaData = $authenticationService->getDataForMfaLogin();
//
//        if ($mfaData === null) {
//            throw new Exception(Craft::t('app', 'User not found'));
//        }
//
//        $user = $mfaData['user'];
//
//        $verified = $authenticationService->verify($user, $verificationCode);
//
//        if ($verified === false) {
//            return $this->asFailure(
//                Craft::t('app', 'Could not verify.'),
//            );
//        }
//
//        return $this->asSuccess(
//            Craft::t('app', 'Verified'),
//        );
//    }
}
