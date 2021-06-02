<?php
declare(strict_types=1);
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\authentication\base\Type;
use craft\authentication\Chain;
use craft\authentication\type\mfa\AuthenticatorCode;
use craft\elements\User;
use craft\helpers\Authentication as AuthenticationHelper;
use craft\services\Authentication;
use craft\web\Controller;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * The AuthenticationController class is a controller that handles various authentication related tasks.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AuthenticationController extends Controller
{
    protected $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;

    /** @var
     * string The session variable name to use to store whether user wants to be remembered.
     */
    private const REMEMBER_ME = 'authChain.rememberMe';

    /**
     * @return Response
     * @throws BadRequestHttpException
     * @throws InvalidConfigException
     */
    public function actionPerformAuthentication(): Response
    {
        $this->requireAcceptsJson();
        $scenario = Authentication::CP_AUTHENTICATION_CHAIN;
        $request = Craft::$app->getRequest();
        $stepType = $request->getBodyParam('stepType', '');
        $chain = Craft::$app->getAuthentication()->getAuthenticationChain($scenario);
        $switch = !empty($request->getBodyParam('switch'));

        if ($switch) {
            return $this->_switchStep($chain, $stepType);
        }

        try {
            $step = $chain->getNextAuthenticationStep($stepType);
        } catch (InvalidConfigException $exception) {
            throw new BadRequestHttpException('Unable to authenticate', 0, $exception);
        }

        $session = Craft::$app->getSession();
        $success = false;

        if ($step !== null) {
            $data = [];

            if ($fields = $step->getFields()) {
                foreach ($fields as $fieldName) {
                    if ($value = $request->getBodyParam($fieldName)) {
                        $data[$fieldName] = $value;
                    }
                }
            }

            $success = $chain->performAuthenticationStep($stepType, $data);

            if ($success && $request->getBodyParam('rememberMe')) {
                $session->set(self::REMEMBER_ME, true);
            }
        }

        if ($chain->getIsComplete()) {
            $generalConfig = Craft::$app->getConfig()->getGeneral();

            if ($session->get(self::REMEMBER_ME) && $generalConfig->rememberedUserSessionDuration !== 0) {
                $duration = $generalConfig->rememberedUserSessionDuration;
            } else {
                $duration = $generalConfig->userSessionDuration;
            }

            Craft::$app->getUser()->login($chain->getAuthenticatedUser(), $duration);
            $session->remove(self::REMEMBER_ME);

            $userSession = Craft::$app->getUser();
            $returnUrl = $userSession->getReturnUrl();
            $userSession->removeReturnUrl();

            return $this->asJson([
                'success' => true,
                'returnUrl' => $returnUrl
            ]);
        }

        $output = [
            'message' => $session->getNotice(),
            'error' => $session->getError(),
        ];

        if ($success) {
            /** @var Type $step */
            $step = $chain->getNextAuthenticationStep();
            $output['stepComplete'] = true;
            $output['stepType'] = $step->getStepType();
            $output['html'] = $step->getInputFieldHtml();
            $output['footHtml'] = Craft::$app->getView()->getBodyHtml();
        }

        $output['alternatives'] = $chain->getAlternativeSteps(get_class($step));

        return $this->asJson($output);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     * @throws InvalidConfigException
     */
    public function actionRecoverAccount(): Response
    {
        $this->requireAcceptsJson();

        // Set up the recovery chain
        $scenario = Authentication::CP_RECOVERY_CHAIN;
        $request = Craft::$app->getRequest();
        $stepType = $request->getRequiredBodyParam('stepType');
        $authenticationService = Craft::$app->getAuthentication();
        $recoveryChain = $authenticationService->getAuthenticationChain($scenario);
        $switch = !empty($request->getBodyParam('switch'));

        if ($switch) {
            return $this->_switchStep($recoveryChain, $stepType);
        }

        if (!$recoveryChain) {
            throw new BadRequestHttpException('Unable to recover account');
        }

        try {
            $step = $recoveryChain->getNextAuthenticationStep($stepType);
        } catch (InvalidConfigException $exception) {
            throw new BadRequestHttpException('Unable to recover account', 0, $exception);
        }

        $session = Craft::$app->getSession();
        $success = false;

        if ($step !== null) {
            $data = [];

            if ($fields = $step->getFields()) {
                foreach ($fields as $fieldName) {
                    if ($value = $request->getBodyParam($fieldName)) {
                        $data[$fieldName] = $value;
                    }
                }
            }

            $success = $recoveryChain->performAuthenticationStep($stepType, $data);
        }

        $output = [];

        if ($recoveryChain->getIsComplete()) {
            $user = $recoveryChain->getAuthenticatedUser();
            $session->setNotice(Craft::t('app', 'Password reset email sent.'));

            if ($user) {
                $sendResult = Craft::$app->getUsers()->sendPasswordResetEmail($user);

                if (!$sendResult) {
                    $session->setError(Craft::t('app', 'There was a problem sending the password reset email.'));
                }
            }

            // If successfully completed recovery, invalidate the chain state.
            // TODO track only one chain per session
            $authenticationService->invalidateAuthenticationState(Authentication::CP_RECOVERY_CHAIN);

            $output['success'] = true;
        } else if ($success) {
            /** @var Type $step */
            $step = $recoveryChain->getNextAuthenticationStep();
            $output['stepComplete'] = true;
            $output['stepType'] = $step->getStepType();
            $output['html'] = $step->getInputFieldHtml();
            $output['footHtml'] = Craft::$app->getView()->getBodyHtml();
        }

        $output['message'] = $session->getNotice();
        $output['error'] = $session->getError();

        return $this->asJson($output);
    }

    public function actionUpdateAuthenticatorSettings(): Response
    {
        $this->requireAcceptsJson();
        $this->requireLogin();
        $this->requireElevatedSession();

        $userSession = Craft::$app->getUser();
        $currentUser = $userSession->getIdentity();

        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();
        $output = [];
        $message = '';

        if (Craft::$app->getEdition() === Craft::Pro) {
            $code1 = $request->getBodyParam('verification-code-1');
            $code2 = $request->getBodyParam('verification-code-2');
            $detach = $request->getBodyParam('detach');

            if (!empty($code1) || !empty($code2)) {
                $authenticator = AuthenticationHelper::getCodeAuthenticator();

                $authenticator->setWindow(4);
                $existingSecret = $session->get(AuthenticatorCode::AUTHENTICATOR_SECRET_SESSION_KEY);
                $firstTimestamp = $authenticator->verifyKeyNewer($existingSecret, $code1, 100);

                if ($firstTimestamp) {
                    // Ensure sequence of two codes
                    $secondTimestamp = $authenticator->verifyKeyNewer($existingSecret, $code2, $firstTimestamp);

                    if ($secondTimestamp) {
                        $currentUser->saveAuthenticator($existingSecret, $secondTimestamp);
                        $session->remove(AuthenticatorCode::AUTHENTICATOR_SECRET_SESSION_KEY);
                        $message = Craft::t('app', 'Successfully attached the authenticator.');
                    }
                } else {
                    $message = Craft::t('app', 'Failed to verify two consecutive codes.');
                }
            } else if (!empty($detach)) {

                if ($detach === 'detach') {
                    $currentUser->removeAuthenticator();
                    $message = Craft::t('app', 'Successfully detached the authenticator.');
                } else {
                    $message = Craft::t('app', 'Failed to detach the authenticator.');
                }
            }

        }

        if ($message) {
            $output['message'] = $message;
        }

        $step = new AuthenticatorCode();

        $output['html'] = $step->getUserSetupFormHtml($currentUser);

        return $this->asJson($output);
    }

    /**
     * Switch to an alternative step on the auth chain.
     *
     * @param Chain $authenticationChain
     * @param string $stepType
     * @return Response
     * @throws InvalidConfigException
     */
    private function _switchStep(Chain $authenticationChain, string $stepType): Response
    {
        $step = $authenticationChain->switchStep($stepType);
        $session = Craft::$app->getSession();

        $output = [
            'html' => $step->getInputFieldHtml(),
            'footHtml' => Craft::$app->getView()->getBodyHtml(),
            'alternatives' => $authenticationChain->getAlternativeSteps(get_class($step)),
            'stepType' => $step->getStepType(),
            'message' => $session->getNotice(),
            'error' => $session->getError(),
        ];

        return $this->asJson($output);
    }
}
