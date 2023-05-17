<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\auth\ProviderInterface;
use craft\elements\User as UserElement;
use craft\errors\AuthFailedException;
use craft\errors\AuthProviderNotFoundException;
use craft\helpers\User as UserHelper;
use craft\web\Controller;
use yii\web\HttpException;
use yii\web\Response;

class AuthController extends Controller
{
    /**
     * The session key used to store the type of request made.  The request type is retrieved
     * when a response comes back.
     */
    const SESSION_KEY = "Auth";

    /**
     * A request type where a user should be logged into Craft
     */
    const REQUEST_TYPE_LOGIN = "Login";

    /**
     * A request type where a user should be logged out of Craft
     */
    const REQUEST_TYPE_LOGOUT = "Logout";

    /**
     * A request type where only the identity session check should be performed
     */
    const REQUEST_TYPE_SESSION = "Session";

    /**
     * @inheritdoc
     */
    protected array|bool|int $allowAnonymous = [
        'request-login' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'request-session' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'response' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
    ];

    /**
     * Perform a login request.
     *
     * @param string $provider
     * @return Response|null
     * @throws AuthProviderNotFoundException
     * @throws HttpException
     * @throws \craft\errors\MissingComponentException
     */
    public function actionRequestLogin(string $provider): ?Response
    {
        $authProvider = Craft::$app->getAuth()->getProviderByHandle($provider);

        Craft::$app->getSession()->set(self::SESSION_KEY, self::REQUEST_TYPE_LOGIN);

        try {
            if ($authProvider->handleLoginRequest()) {
                return $this->response;
            }
        } catch (AuthFailedException $exception) {
            throw new HttpException(400, $exception->getMessage(), previous: $exception);
        }

        return $this->handleFailedRequest();
    }

    /**
     * Perform a logout request.
     *
     * @param string $provider
     * @return null|Response
     * @throws AuthProviderNotFoundException
     * @throws \craft\errors\MissingComponentException
     */
    public function actionRequestLogout(string $provider): ?Response
    {
        $authProvider = Craft::$app->getAuth()->getProviderByHandle($provider);

        Craft::$app->getSession()->set(self::SESSION_KEY, self::REQUEST_TYPE_LOGOUT);

        try {
            if ($authProvider->handleLogoutRequest()) {
                return $this->response;
            }
        } catch (AuthFailedException $exception) {
            throw new HttpException(400, $exception->getMessage(), previous: $exception);
        }

        return $this->handleFailedRequest();
    }

    /**
     * Perform a session request.
     *
     * @param string $provider
     * @return Response
     * @throws AuthProviderNotFoundException
     * @throws HttpException
     * @throws \craft\errors\MissingComponentException
     */
    public function actionRequestSession(string $provider): Response
    {
        $authProvider = Craft::$app->getAuth()->getProviderByHandle($provider);

        Craft::$app->getSession()->set(self::SESSION_KEY, self::REQUEST_TYPE_SESSION);

        try {
            if ($authProvider->handleAuthRequest()) {
                return $this->response;
            }
        } catch (AuthFailedException $exception) {
            throw new HttpException(400, $exception->getMessage(), previous: $exception);
        }

        return $this->handleFailedRequest();
    }

    /**
     * Handle the identity provider response.
     *
     * @param string $provider
     * @return Response|null
     * @throws \craft\errors\MissingComponentException
     */
    public function actionResponse(string $provider): ?Response
    {
        switch(Craft::$app->getSession()->get(self::SESSION_KEY)) {
            case self::REQUEST_TYPE_LOGIN:
                return $this->actionLoginResponse($provider);

            case self::REQUEST_TYPE_SESSION:
                return $this->actionSessionResponse($provider);

            case self::REQUEST_TYPE_LOGOUT:
                return $this->actionLogoutResponse($provider);
        }

        return $this->handleFailedResponse();
    }

    public function actionLoginResponse(string $provider): ?Response
    {
        $authProvider = Craft::$app->getAuth()->getProviderByHandle($provider);

        try {
            if ($authProvider->handleLoginResponse()) {
                return $this->handleSuccessfulResponse();
            }
        } catch (AuthFailedException $exception) {
            return $this->handleFailedResponse($exception->identity);
        }

        return $this->handleFailedResponse();
    }

    public function actionLogoutResponse(string $provider): ?Response
    {
        $authProvider = Craft::$app->getAuth()->getProviderByHandle($provider);

        try {
            if ($authProvider->handleLogoutResponse()) {
                return $this->handleSuccessfulResponse();
            }
        } catch (AuthFailedException $exception) {
            return $this->handleFailedResponse($exception->identity);
        }

        return $this->handleFailedResponse();
    }

    public function actionSessionResponse(string $provider): ?Response
    {
        $authProvider = Craft::$app->getAuth()->getProviderByHandle($provider);

        try {
            if ($authProvider->handleAuthResponse()) {
                return $this->handleSuccessfulResponse();
            }
        } catch (AuthFailedException $exception) {
            return $this->handleFailedResponse($exception->identity);
        }

        return $this->handleFailedResponse();
    }

    /**
     * @param string|null $message
     * @return Response|null
     */
    protected function handleFailedRequest(?string $message = null,): ?Response
    {
        return $this->asFailure(
            $message ?? Craft::t('app', 'Unable to initiate an auth request.')
        );
    }

    /**
     * Handles a successful auth response; If the request accepts JSON, a
     *
     * @return Response
     */
    protected function handleSuccessfulResponse(): Response
    {
        // Get the return URL
        $userSession = Craft::$app->getUser();
        $returnUrl = $userSession->getReturnUrl();

        // Clear it out
        $userSession->removeReturnUrl();

        // If this was an Ajax request, just return success:true
        if ($this->request->getAcceptsJson()) {
            $return = [
                'returnUrl' => $returnUrl,
            ];

            if (Craft::$app->getConfig()->getGeneral()->enableCsrfProtection) {
                $return['csrfTokenValue'] = $this->request->getCsrfToken();
            }

            return $this->asJson($return);
        }

        return $this->redirect(
            $returnUrl ?
                Craft::$app->getView()->renderObjectTemplate(
                    $returnUrl,
                    $userSession->getIdentity()
                ) :
                $this->request->getPathInfo()
        );
    }

    protected function handleFailedResponse(?UserElement $user = null, array $routeParams = []): Response
    {
        // Delay randomly between 0 and 1.5 seconds.
        usleep(random_int(0, 1500000));

        $message = UserHelper::getAuthFailureMessage($user);

//        // Fire a 'loginFailure' event
//        $event = new LoginFailureEvent([
//            'authError' => $authError,
//            'message' => $message,
//            'user' => $user,
//        ]);
//        $this->trigger(self::EVENT_LOGIN_FAILURE, $event);

        return $this->asFailure(
            $message,
            data: [
                'errorCode' => $user->authError ?? "unknown",
            ],
            routeParams: array_merge(
                $routeParams,
                [
                    'errorCode' => $user->authError ?? "unknown",
                    'errorMessage' => $message,
                ]
            )
        );
    }
}
