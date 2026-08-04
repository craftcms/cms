<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\enums\CmsEdition;
use craft\errors\AuthProviderNotFoundException;
use craft\errors\SsoFailedException;
use craft\helpers\Json;
use craft\helpers\User as UserHelper;
use craft\web\Controller;
use yii\web\HttpException;
use yii\web\Response;

/**
 * SSO controller
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @internal
 * @since 5.3.0
 */
class SsoController extends Controller
{
    /**
     * @inheritdoc
     */
    protected array|bool|int $allowAnonymous = [
        'request' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'response' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
    ];

    public $enableCsrfValidation = false;

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        Craft::$app->requireEdition(CmsEdition::Enterprise);
        return true;
    }

    /**
     * Perform a login request.
     *
     * @param string|null $provider
     * @return Response|null
     * @throws AuthProviderNotFoundException
     * @throws HttpException
     * @throws \craft\errors\MissingComponentException
     */
    public function actionRequest(?string $provider = null): ?Response
    {
        $authProvider = Craft::$app->getSso()->getProviderByHandle(
            $provider ?? $this->request->getRequiredParam('provider')
        );

        try {
            return $authProvider->handleRequest(
                $this->request,
                $this->response
            );
        } catch (SsoFailedException $exception) {
            throw new HttpException(400, $exception->getMessage(), previous: $exception);
        }
    }

    public function actionResponse(?string $provider = null): ?Response
    {
        $authProvider = Craft::$app->getSso()->getProviderByHandle(
            $provider ?? $this->request->getRequiredParam('provider')
        );

        try {
            if ($authProvider->handleResponse(
                $this->request,
                $this->response
            )) {
                return $this->handleSuccessfulResponse();
            }
        } catch (SsoFailedException $exception) {
            return $this->handleFailedResponse($exception);
        }

        return $this->handleFailedResponse();
    }

    /**
     * @param string|null $message
     * @return Response|null
     */
    protected function handleFailedRequest(?string $message = null): ?Response
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
    protected function handleFailedResponse(\Throwable $exception = null)
    {
        // Delay randomly between 0 and 1.5 seconds.
        usleep(random_int(0, 1500000));

        $user = null;
        $message = Craft::t('app', 'Auth error');

        if ($exception instanceof SsoFailedException) {
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

        throw new HttpException(500, $message, 0, $exception);
    }
}
