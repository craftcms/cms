<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\web\Controller;
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
     * Get all available alternative MFA options for logging in.
     *
     * @return Response|null
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionGetAlternativeMfaOptions(): ?Response
    {
        if (!$this->request->getIsPost()) {
            return null;
        }

        $currentMethod = Craft::$app->getRequest()->getRequiredBodyParam('currentMethod');
        $alternativeOptions = Craft::$app->getMfa()->getAlternativeMfaOptions($currentMethod);

        if ($this->request->getAcceptsJson()) {
            return $this->asSuccess(
                data: ['alternativeOptions' => $alternativeOptions],
            );
        }

        // todo: finish me
        return null;
    }

    /**
     * Return HTML for selected alternative MFA option
     *
     * @return Response|null
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\base\Exception
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionLoadAlternativeMfaOption(): ?Response
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
