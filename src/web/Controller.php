<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use GuzzleHttp\Exception\ClientException;
use yii\base\InvalidArgumentException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\JsonResponseFormatter;
use yii\web\Response as YiiResponse;

/**
 * Controller is a base class that all controllers in Craft extend.
 * It extends Yii’s [[\yii\web\Controller]], overwriting specific methods as required.
 *
 * @property View $view The view object that can be used to render views or view files
 * @method View getView() Returns the view object that can be used to render views or view files
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class Controller extends \yii\web\Controller
{
    // Properties
    // =========================================================================

    /**
     * @var bool|string[] Whether this controller’s actions can be accessed anonymously
     * If set to false, you are required to be logged in to execute any of the given controller's actions.
     * If set to true, anonymous access is allowed for all of the given controller's actions.
     * If the value is an array of action IDs, then you must be logged in for any actions except for the ones in
     * the array list.
     * If you have a controller that where the majority of actions allow anonymous access, but you only want require
     * login on a few, you can set this to true and call [[requireLogin()]] in the individual methods.
     */
    protected $allowAnonymous = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // Enforce $allowAnonymous
        if (
            (is_array($this->allowAnonymous) && (!preg_grep("/{$action->id}/i", $this->allowAnonymous))) ||
            $this->allowAnonymous === false
        ) {
            $this->requireLogin();
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function runAction($id, $params = [])
    {
        try {
            return parent::runAction($id, $params);
        } catch (\Throwable $e) {
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                Craft::$app->getErrorHandler()->logException($e);
                $message = $e->getMessage();
                if ($e instanceof ClientException) {
                    $statusCode = $e->getCode();
                    if (($response = $e->getResponse()) !== null) {
                        $body = Json::decodeIfJson((string)$response->getBody());
                        if (isset($body['message'])) {
                            $message = $body['message'];
                        }
                    }
                } else if ($e instanceof HttpException) {
                    $statusCode = $e->statusCode;
                } else {
                    $statusCode = 500;
                }
                return $this->asErrorJson($message)
                    ->setStatusCode($statusCode);
            }
            throw $e;
        }
    }

    /**
     * Renders a template.
     *
     * @param string $template The name of the template to load
     * @param array $variables The variables that should be available to the template
     * @return YiiResponse
     * @throws InvalidArgumentException if the view file does not exist.
     */
    public function renderTemplate(string $template, array $variables = []): YiiResponse
    {
        $response = Craft::$app->getResponse();
        $headers = $response->getHeaders();

        // Set the MIME type for the request based on the matched template's file extension (unless the
        // Content-Type header was already set, perhaps by the template via the {% header %} tag)
        if (!$headers->has('content-type')) {
            $templateFile = Craft::$app->getView()->resolveTemplate($template);
            $extension = pathinfo($templateFile, PATHINFO_EXTENSION) ?: 'html';

            if (($mimeType = FileHelper::getMimeTypeByExtension('.'.$extension)) === null) {
                $mimeType = 'text/html';
            }

            $headers->set('content-type', $mimeType.'; charset='.$response->charset);
        }

        // Render and return the template
        $response->data = $this->getView()->renderPageTemplate($template, $variables);

        // Prevent a response formatter from overriding the content-type header
        $response->format = YiiResponse::FORMAT_RAW;

        return $response;
    }

    /**
     * Redirects the user to the login template if they're not logged in.
     */
    public function requireLogin()
    {
        $user = Craft::$app->getUser();

        if ($user->getIsGuest()) {
            $user->loginRequired();
            Craft::$app->end();
        }
    }

    /**
     * Throws a 403 error if the current user is not an admin.
     *
     * @throws ForbiddenHttpException if the current user is not an admin
     */
    public function requireAdmin()
    {
        // First make sure someone's actually logged in
        $this->requireLogin();

        // Make sure they're an admin
        if (!Craft::$app->getUser()->getIsAdmin()) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }
    }

    /**
     * Checks whether the current user has a given permission, and ends the request with a 403 error if they don’t.
     *
     * @param string $permissionName The name of the permission.
     * @throws ForbiddenHttpException if the current user doesn’t have the required permission
     */
    public function requirePermission(string $permissionName)
    {
        if (!Craft::$app->getUser()->checkPermission($permissionName)) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }
    }

    /**
     * Checks whether the current user can perform a given action, and ends the request with a 403 error if they don’t.
     *
     * @param string $action The name of the action to check.
     * @throws ForbiddenHttpException if the current user is not authorized
     */
    public function requireAuthorization(string $action)
    {
        if (!Craft::$app->getSession()->checkAuthorization($action)) {
            throw new ForbiddenHttpException('User is not authorized to perform this action');
        }
    }

    /**
     * Requires that the user has an elevated session.
     *
     * @throws ForbiddenHttpException if the current user does not have an elevated session
     */
    public function requireElevatedSession()
    {
        if (!Craft::$app->getUser()->getHasElevatedSession()) {
            throw new ForbiddenHttpException(Craft::t('app', 'This action may only be performed with an elevated session.'));
        }
    }

    /**
     * Throws a 400 error if this isn’t a POST request
     *
     * @throws BadRequestHttpException if the request is not a post request
     */
    public function requirePostRequest()
    {
        if (!Craft::$app->getRequest()->getIsPost()) {
            throw new BadRequestHttpException('Post request required');
        }
    }

    /**
     * Throws a 400 error if the request doesn't accept JSON.
     *
     * @throws BadRequestHttpException if the request doesn't accept JSON
     */
    public function requireAcceptsJson()
    {
        if (!Craft::$app->getRequest()->getAcceptsJson()) {
            throw new BadRequestHttpException('Request must accept JSON in response');
        }
    }

    /**
     * Throws a 400 error if the current request doesn’t have a valid token.
     *
     * @throws BadRequestHttpException if the request does not have a valid token
     */
    public function requireToken()
    {
        if (!Craft::$app->getRequest()->getQueryParam(Craft::$app->getConfig()->getGeneral()->tokenParam)) {
            throw new BadRequestHttpException('Valid token required');
        }
    }

    /**
     * Redirects to the URI specified in the POST.
     *
     * @param mixed $object Object containing properties that should be parsed for in the URL.
     * @param string|null $default The default URL to redirect them to, if no 'redirect' parameter exists. If this is left
     * null, then the current request’s path will be used.
     * @return YiiResponse
     * @throws BadRequestHttpException if the redirect param was tampered with
     */
    public function redirectToPostedUrl($object = null, string $default = null): YiiResponse
    {
        $url = Craft::$app->getRequest()->getValidatedBodyParam('redirect');

        if ($url === null) {
            if ($default !== null) {
                $url = $default;
            } else {
                $url = Craft::$app->getRequest()->getPathInfo();
            }
        }

        if ($object) {
            $url = Craft::$app->getView()->renderObjectTemplate($url, $object);
        }

        return $this->redirect($url);
    }

    /** @noinspection ArrayTypeOfParameterByDefaultValueInspection */
    /**
     * Sets the response format of the given data as JSONP.
     *
     * @param mixed $data The data that should be formatted.
     * @return YiiResponse A response that is configured to send `$data` formatted as JSON.
     * @see YiiResponse::$format
     * @see YiiResponse::FORMAT_JSONP
     * @see JsonResponseFormatter
     */
    public function asJsonP($data): YiiResponse
    {
        $response = Craft::$app->getResponse();
        $response->data = $data;
        $response->format = YiiResponse::FORMAT_JSONP;

        return $response;
    }

    /** @noinspection ArrayTypeOfParameterByDefaultValueInspection */
    /**
     * Sets the response format of the given data as RAW.
     *
     * @param mixed $data The data that should *not* be formatted.
     * @return YiiResponse A response that is configured to send `$data` without formatting.
     * @see YiiResponse::$format
     * @see YiiResponse::FORMAT_RAW
     */
    public function asRaw($data): YiiResponse
    {
        $response = Craft::$app->getResponse();
        $response->data = $data;
        $response->format = YiiResponse::FORMAT_RAW;

        return $response;
    }

    /**
     * Responds to the request with a JSON error message.
     *
     * @param string $error The error message.
     * @return YiiResponse
     */
    public function asErrorJson(string $error): YiiResponse
    {
        return $this->asJson(['error' => $error]);
    }

    /**
     * @inheritdoc
     * @return YiiResponse
     */
    public function redirect($url, $statusCode = 302): YiiResponse
    {
        if (is_string($url)) {
            $url = UrlHelper::url($url);
        }

        if ($url !== null) {
            return Craft::$app->getResponse()->redirect($url, $statusCode);
        }

        return $this->goHome();
    }
}
