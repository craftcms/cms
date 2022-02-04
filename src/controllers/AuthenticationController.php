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
use craft\authentication\State;
use craft\authentication\type\AuthenticatorCode;
use craft\authentication\type\WebAuthn;
use craft\authentication\webauthn\CredentialRepository;
use craft\elements\User;
use craft\helpers\Authentication as AuthenticationHelper;
use craft\helpers\Json;
use craft\web\Controller;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\Server;
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
    protected $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE;

    private ?State $_state = null;

    /** @var
     * string The session variable name to use to store whether user wants to be remembered.
     */
    private const REMEMBER_ME = 'auth.rememberMe';

    /** @var
     * string The session variable name to use the entered user name.
     */
    private const AUTH_USER_NAME = 'auth.userName';

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        $this->_state = Craft::$app->getAuthentication()->getAuthState();
        return parent::beforeAction($action);
    }

    /**
     * @inheritdoc
     */
    public function afterAction($action, $result)
    {
        if ($this->_state) {
            Craft::$app->getAuthentication()->persistAuthenticationState($this->_state);
        } else {
            Craft::$app->getAuthentication()->invalidateAuthenticationState();
        }

        return parent::afterAction($action, $result);
    }


    /**
     * Start a new authentication chain.
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionStartAuthentication(): Response
    {
        $this->requireAcceptsJson();
        $request = Craft::$app->getRequest();
        $username = $request->getBodyParam('loginName');

        if (empty($username)) {
            $this->_state = null;
            return $this->asJson(['loginFormHtml' => Craft::$app->getView()->renderTemplate('_special/login_form', [
                'user' => null,
                'username' => '',
                'authState' => null,
            ])]);
        }

        $user = $this->_getUser($username);

        if (!$user) {
            return $this->asErrorJson(Craft::t('app', 'Invalid username or email.'));
        }

        Craft::$app->getSession()->set(self::AUTH_USER_NAME, $username);

        $authentication = Craft::$app->getAuthentication();
        $authentication->invalidateAuthenticationState();
        $this->_state->setUser($user);
        $currentStep = $this->_state->getNextStep()->getStepType();
        $session = Craft::$app->getSession();

        return $this->asJson([
            'loginFormHtml' => Craft::$app->getView()->renderTemplate('_special/login_form', [
                'user' => $user,
                'username' => $user->username,
                'authState' => $this->_state,
                'alternativeSteps' => $this->_state->getAlternativeSteps()
            ]),
            'footHtml' => Craft::$app->getView()->getBodyHtml(),
            'stepType' => $currentStep,
            'message' => $session->getNotice(),
            'error' => $session->getError(),
        ]);
    }

    /**
     * Perform an authentication step.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws InvalidConfigException
     * @throws \Exception
     */
    public function actionPerformAuthentication(): Response
    {
        $this->requireAcceptsJson();
        $request = Craft::$app->getRequest();
        $username = Craft::$app->getSession()->get(self::AUTH_USER_NAME) ?? Craft::$app->getUser()->getRememberedUsername();
        $user = null;

        if ($username) {
            $user = $this->_getUser($username);
        }

        if (!$user) {
            throw new BadRequestHttpException('Unable to determine user');
        }

        $alternateStep = $request->getBodyParam('alternateStep');

        if (!empty($alternateStep)) {
            $this->_state->selectAlternateStep($alternateStep);

            return $this->asJson([
                'html' => $this->_state->getNextStep()->getInputFieldHtml(),
                'footHtml' => Craft::$app->getView()->getBodyHtml(),
                'alternatives' => $this->_state->getAlternativeSteps(),
                'stepType' => $this->_state->getNextStep()->getStepType(),
            ]);
        }

        $step = $this->_state->getNextStep();

        $session = Craft::$app->getSession();

        $data = [];
        if ($fields = $step->getFields()) {
            foreach ($fields as $fieldName) {
                if ($value = $request->getBodyParam($fieldName)) {
                    $data[$fieldName] = $value;
                }
            }
        }

        $success = $step->authenticate($data, $user);

        if ($success) {
            $this->_state->completeStep();
        }

        if ($success && $request->getBodyParam('rememberMe')) {
            $session->set(self::REMEMBER_ME, true);
        }

        if (!$this->_state->getIsAuthenticated()) {
            $output = [
                'message' => $session->getNotice(),
                'error' => $session->getError(),
            ];

            /** @var Type $step */
            $step = $this->_state->getNextStep();

            if ($success) {
                $output['stepComplete'] = true;
                $output['stepType'] = $step->getStepType();
                $output['html'] = $step->getInputFieldHtml();
                $output['footHtml'] = Craft::$app->getView()->getBodyHtml();
            }

            $output['alternatives'] = $this->_state->getAlternativeSteps();

            return $this->asJson($output);
        }

        $generalConfig = Craft::$app->getConfig()->getGeneral();

        if ($session->get(self::REMEMBER_ME) && $generalConfig->rememberedUserSessionDuration !== 0) {
            $duration = $generalConfig->rememberedUserSessionDuration;
        } else {
            $duration = $generalConfig->userSessionDuration;
        }

        $userComponent = Craft::$app->getUser();
        $userComponent->login($this->_state->getAuthenticatedUser(), $duration);
        $session->remove(self::REMEMBER_ME);
        $this->_state = null;

        $returnUrl = $userComponent->getReturnUrl();
        $userComponent->removeReturnUrl();
        $userComponent->sendUsernameCookie($user);

        return $this->asJson([
            'success' => true,
            'returnUrl' => $returnUrl
        ]);
    }

    /**
     * Detach web authn credentials
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws \Throwable
     */
    public function actionDetachWebAuthnCredentials(): Response
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        $this->requireElevatedSession();
        $userSession = Craft::$app->getUser();
        $currentUser = $userSession->getIdentity();

        $credentialId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        return $this->asJson(['success' => (new CredentialRepository())->deleteCredentialSourceForUser($currentUser, $credentialId)]);
    }

    /**
     * Attach WebAuthn credentials.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionAttachWebAuthnCredentials(): Response
    {
        $this->requireAcceptsJson();
        $this->requireLogin();
        $this->requireElevatedSession();

        $request = Craft::$app->getRequest();
        $payload = $request->getRequiredBodyParam('credentials');
        $credentialName = $request->getBodyParam('credentialName', '');

        $userSession = Craft::$app->getUser();
        $currentUser = $userSession->getIdentity();

        $output = [];

        try {
            $credentialRepository = new CredentialRepository();

            $server = new Server(
                WebAuthn::getRelayingPartyEntity(),
                $credentialRepository
            );

            $options = WebAuthn::getCredentialCreationOptions($currentUser);
            $credentials = $server->loadAndCheckAttestationResponse(Json::encode($payload), $options, $request->asPsr7());
            $credentialRepository->saveNamedCredentialSource($credentials, $credentialName);

            $step = new WebAuthn();
            $output['html'] = $step->getUserSetupFormHtml($currentUser);
            $output['footHtml'] = Craft::$app->getView()->getBodyHtml();
        } catch (\Throwable $exception) {
            Craft::$app->getErrorHandler()->logException($exception);
            $output['error'] = Craft::t('app', 'Something went wrong when attempting to attach credentials.');
        }

        return $this->asJson($output);
    }

    /**
     * Update authenticator settings.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws \PragmaRX\Google2FA\Exceptions\Google2FAException
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\web\ForbiddenHttpException
     */
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
     * Return a user by username, faking it, if required by config.
     *
     * @param string $username
     * @return User|null
     */
    private function _getUser(string $username): ?User {
        $user = Craft::$app->getUsers()->getUserByUsernameOrEmail($username);

        if (!$user && Craft::$app->getConfig()->getGeneral()->preventUserEnumeration) {
            $user = AuthenticationHelper::getFakeUser($username);
        }

        return $user;
    }
}
