<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web;

use Craft;
use craft\app\helpers\Header;
use craft\app\helpers\Io;
use craft\app\helpers\Url;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response as YiiResponse;

/**
 * Controller is a base class that all controllers in Craft extend.
 *
 * It extends Yii’s [[\yii\web\Controller]], overwriting specific methods as required.
 *
 * @method View getView() Returns the view object that can be used to render views or view files
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
abstract class Controller extends \yii\web\Controller
{
    // Properties
    // =========================================================================

    /**
     * @var boolean|string[] Whether this controller’s actions can be accessed anonymously
     *
     * If set to false, you are required to be logged in to execute any of the given controller's actions.
     *
     * If set to true, anonymous access is allowed for all of the given controller's actions.
     *
     * If the value is an array of action IDs, then you must be logged in for any actions except for the ones in
     * the array list.
     *
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
        if (parent::beforeAction($action)) {
            // Enforce $allowAnonymous
            if (
                (is_array($this->allowAnonymous) && (!preg_grep("/{$action->id}/i", $this->allowAnonymous))) ||
                $this->allowAnonymous === false
            ) {
                $this->requireLogin();
            }

            return true;
        }

        return false;
    }

    /**
     * Renders a template.
     *
     * @param mixed $template      The name of the template to load in a format supported by
     *                             [[\craft\app\web\View::resolveTemplate()]], or a [[\craft\app\web\twig\StringTemplate]] object.
     * @param array $variables     The variables that should be available to the template.
     *
     * @return string The rendering result
     * @throws InvalidParamException if the view file does not exist.
     */
    public function renderTemplate($template, $variables = [])
    {
        // Set the MIME type for the request based on the matched template's file extension (unless the
        // Content-Type header was already set, perhaps by the template via the {% header %} tag)
        if (!Header::isHeaderSet('Content-Type')) {
            $templateFile = Craft::$app->getView()->resolveTemplate($template);
            $extension = Io::getExtension($templateFile, 'html');

            if ($extension == 'twig') {
                $extension = 'html';
            }

            Header::setContentTypeByExtension($extension);
        }

        // Set the charset header
        Header::setHeader(['charset' => 'utf-8']);

        // Render and return the template
        return $this->getView()->renderPageTemplate($template, $variables);
    }

    /**
     * Redirects the user to the login template if they're not logged in.
     *
     * @return void
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
     * @return void
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
     *
     * @return void
     * @throws ForbiddenHttpException if the current user doesn’t have the required permission
     */
    public function requirePermission($permissionName)
    {
        if (!Craft::$app->getUser()->checkPermission($permissionName)) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }
    }

    /**
     * Checks whether the current user can perform a given action, and ends the request with a 403 error if they don’t.
     *
     * @param string $action The name of the action to check.
     *
     * @return void
     * @throws ForbiddenHttpException if the current user is not authorized
     */
    public function requireAuthorization($action)
    {
        if (!Craft::$app->getSession()->checkAuthorization($action)) {
            throw new ForbiddenHttpException('User is not authorized to perform this action');
        }
    }

    /**
     * Requires that the user has an elevated session.
     *
     * @return void
     * @throws ForbiddenHttpException if the current user does not have an elevated session
     */
    public function requireElevatedSession()
    {
        if (!Craft::$app->getUser()->getHasElevatedSession()) {
            throw new ForbiddenHttpException(403, Craft::t('app', 'This action may only be performed with an elevated session.'));
        }
    }

    /**
     * Throws a 400 error if this isn’t a POST request
     *
     * @return void
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
     * @return void
     * @throws BadRequestHttpException if the request doesn't accept JSON
     */
    public function requireAcceptsJson()
    {
        if (!Craft::$app->getRequest()->getAcceptsJson()) {
            //throw new BadRequestHttpException('Request must accept JSON in response');
        }
    }

    /**
     * Throws a 400 error if the current request doesn’t have a valid token.
     *
     * @return void
     * @throws BadRequestHttpException if the request does not have a valid token
     */
    public function requireToken()
    {
        if (!Craft::$app->getRequest()->getQueryParam(Craft::$app->getConfig()->get('tokenParam'))) {
            throw new BadRequestHttpException('Valid token required');
        }
    }

    /**
     * Redirects to the URI specified in the POST.
     *
     * @param mixed  $object  Object containing properties that should be parsed for in the URL.
     * @param string $default The default URL to redirect them to, if no 'redirect' parameter exists. If this is left
     *                        null, then the current request’s path will be used.
     *
     * @return YiiResponse
     * @throws BadRequestHttpException if the redirect param was tampered with
     */
    public function redirectToPostedUrl($object = null, $default = null)
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
            $url = Craft::$app->getView()->renderObjectTemplate($url, $object, true);
        }

        return $this->redirect($url);
    }

    /**
     * Sets the response format of the given data as JSON.
     *
     * @param mixed $var The array that should be JSON-encoded.
     *
     * @return YiiResponse The response object.
     */
    public function asJson($var = [])
    {
        $response = Craft::$app->getResponse();
        $response->data = $var;
        $response->format = Response::FORMAT_JSON;

        return $response;
    }

    /**
     * Sets the response format of the given data as JSONP.
     *
     * @param mixed $var The array that should be JSON-encoded.
     *
     * @return YiiResponse The response object.
     */
    public function asJsonP($var = [])
    {
        $response = Craft::$app->getResponse();
        $response->data = $var;
        $response->format = Response::FORMAT_JSONP;

        return $response;
    }

    /**
     * Sets the response format of the given data as RAW.
     *
     * @param mixed $var The RAW array data.
     *
     * @return YiiResponse The response object.
     */
    public function asRaw($var = [])
    {
        $response = Craft::$app->getResponse();
        $response->data = $var;
        $response->format = Response::FORMAT_RAW;

        return $response;
    }

    /**
     * Sets the response format of the given data as XML.
     *
     * @param mixed $var The array that should be XML-encoded.
     *
     * @return YiiResponse The response object.
     */
    public function asXml($var = [])
    {
        $response = Craft::$app->getResponse();
        $response->data = $var;
        $response->format = Response::FORMAT_XML;

        return $response;
    }

    /**
     * Responds to the request with a JSON error message.
     *
     * @param string $error The error message.
     *
     * @return YiiResponse
     */
    public function asErrorJson($error)
    {
        return $this->asJson(['error' => $error]);
    }

    /**
     * @inheritdoc
     *
     * @return YiiResponse
     */
    public function redirect($url, $statusCode = 302)
    {
        if (is_string($url)) {
            $url = Url::getUrl($url);
        }

        if ($url !== null) {
            return Craft::$app->getResponse()->redirect($url, $statusCode);
        }

        return $this->goHome();
    }
}
