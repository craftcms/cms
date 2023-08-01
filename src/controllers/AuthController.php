<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\elements\User as UserElement;
use craft\errors\AuthFailedException;
use craft\errors\AuthProviderNotFoundException;
use craft\helpers\Json;
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
        'login-response' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
    ];

    public $enableCsrfValidation = false;

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
            return $authProvider->handleLoginRequest($this->request, $this->response);
        } catch (AuthFailedException $exception) {
            throw new HttpException(400, $exception->getMessage(), previous: $exception);
        }
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
            return $authProvider->handleAuthRequest($this->request, $this->response);
        } catch (AuthFailedException $exception) {
            throw new HttpException(400, $exception->getMessage(), previous: $exception);
        }
    }

    /**
     * Handle the identity provider response.
     *
     * TODO I don't think this is needed
     * @param string $provider
     * @return Response|null
     * @throws \craft\errors\MissingComponentException
     */
    public function actionResponse(string $provider): ?Response
    {
        switch (Craft::$app->getSession()->get(self::SESSION_KEY)) {

            case self::REQUEST_TYPE_SESSION:
                return $this->actionSessionResponse($provider);

            default:
                return $this->actionLoginResponse($provider);
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
            return $this->handleFailedResponse($exception);
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
            return $this->handleFailedResponse($exception);
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

    /**
     * @param \Exception|null $exception
     * @throws HttpException
     */
    protected function handleFailedResponse(\Exception $exception = null)
    {
        // Delay randomly between 0 and 1.5 seconds.
        usleep(random_int(0, 1500000));

        $user = null;
        $message = Craft::t('app', 'Auth error');

        if ($exception instanceof AuthFailedException) {
            $user = $exception->identity;
            $message =
                UserHelper::getAuthFailureMessage($exception->identity) ??
                $message;
        }

        // Log some context around the error
        $user?->hasErrors() ? Craft::error(
            sprintf(
                "%s. Errors: %s.",
                $message,
                Json::encode($user->getErrors())
            ),
            "auth"
        ) : Craft::error(
            $message,
            "auth"
        );

        throw new HttpException(500, $message, null, $exception);
    }
}
