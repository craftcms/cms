<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
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
class AuthenticationController extends Controller
{
    /**
     * @inheritdoc
     */
    protected array|bool|int $allowAnonymous = true;

//    public function actionGetQrCode(): Response
//    {
//
//    }

    public function actionVerify(): Response
    {
        $verificationCode = Craft::$app->request->getRequiredBodyParam('verificationCode');
        if (empty($verificationCode)) {
            return $this->asFailure('Please provide a verification code');
        }

        $authenticationService = Craft::$app->getAuthentication();

        $mfaData = $authenticationService->getDataForMfaLogin();
        if ($mfaData === null) {
            throw new Exception(Craft::t('app', 'User not found'));
        }

        $user = $mfaData['user'];

        $verified = $authenticationService->verify($user, $verificationCode);

        if ($verified === false) {
            return $this->asFailure(
                Craft::t('app', 'Couldn’t verify.'),
            );
        }

        return $this->asSuccess(
            Craft::t('app', 'Verified'),
        );
    }
}
