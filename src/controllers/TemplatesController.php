<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\helpers\App;
use craft\helpers\Template;
use craft\web\Controller;
use ErrorException;
use yii\base\UserException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * The TemplatesController class is a controller that handles various template rendering related tasks for both the
 * control panel and front-end of a Craft site.
 * Note that all actions in the controller are open to do not require an authenticated Craft session in order to execute.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class TemplatesController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $allowAnonymous = true;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        $actionSegments = Craft::$app->getRequest()->getActionSegments();
        if (isset($actionSegments[0]) && strtolower($actionSegments[0]) === 'templates') {
            throw new ForbiddenHttpException();
        }

        return parent::beforeAction($action);
    }

    /**
     * Renders a template.
     *
     * @param string $template
     * @param array $variables
     * @return Response
     * @throws NotFoundHttpException if the requested template cannot be found
     */
    public function actionRender(string $template, array $variables = []): Response
    {
        // Does that template exist?
        if (!$this->getView()->doesTemplateExist($template)) {
            throw new NotFoundHttpException('Template not found: ' . $template);
        }

        // Merge any additional route params
        $routeParams = Craft::$app->getUrlManager()->getRouteParams();
        unset($routeParams['template'], $routeParams['template']);
        $variables = array_merge($variables, $routeParams);

        return $this->renderTemplate($template, $variables);
    }

    /**
     * Shows the 'offline' template.
     *
     * @return Response
     */
    public function actionOffline(): Response
    {
        // If this is a site request, make sure the offline template exists
        $view = $this->getView();
        if (Craft::$app->getRequest()->getIsSiteRequest() && !$view->doesTemplateExist('offline')) {
            $view->setTemplateMode($view::TEMPLATE_MODE_CP);
        }

        // Output the offline template
        return $this->renderTemplate('offline');
    }

    /**
     * Renders the Manual Update notification template.
     *
     * @return Response
     */
    public function actionManualUpdateNotification(): Response
    {
        return $this->renderTemplate('_special/dbupdate');
    }

    /**
     * Renders the Project Config Sync kickoff template.
     *
     * @return Response
     */
    public function actionConfigSyncKickoff(): Response
    {
        return $this->renderTemplate('_special/configsync');
    }

    /**
     * Renders the incompatible project config alert template.
     *
     * @return Response
     */
    public function actionIncompatibleConfigAlert(): Response
    {
        return $this->renderTemplate('_special/incompatibleconfigs');
    }

    /**
     * @return Response|null
     * @throws ServerErrorHttpException if it's an Ajax request and the server doesn’t meet Craft’s requirements
     */
    public function actionRequirementsCheck()
    {
        // Run the requirements checker
        $reqCheck = new \RequirementsChecker();
        $dbConfig = Craft::$app->getConfig()->getDb();
        $reqCheck->dsn = $dbConfig->dsn;
        $reqCheck->dbDriver = $dbConfig->driver;
        $reqCheck->dbUser = $dbConfig->user;
        $reqCheck->dbPassword = $dbConfig->password;

        $reqCheck->checkCraft();

        if ($reqCheck->result['summary']['errors'] > 0) {
            // Coming from Updater.php
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                $message = '<br /><br />';

                foreach ($reqCheck->getResult()['requirements'] as $req) {
                    if ($req['error'] === true) {
                        $message .= $req['memo'] . '<br />';
                    }
                }

                throw new ServerErrorHttpException(Craft::t('app', 'The update can’t be installed :( {message}', ['message' => $message]));
            }

            return $this->renderTemplate('_special/cantrun', [
                'reqCheck' => $reqCheck
            ]);
        }

        // Cache the base path.
        Craft::$app->getCache()->set('basePath', Craft::$app->getBasePath());

        return null;
    }

    /**
     * Renders an error template.
     *
     * @return Response
     */
    public function actionRenderError(): Response
    {
        /** @var $errorHandler \yii\web\ErrorHandler */
        $errorHandler = Craft::$app->getErrorHandler();
        $exception = $errorHandler->exception;

        if ($exception instanceof HttpException && $exception->statusCode) {
            $statusCode = (string)$exception->statusCode;
        } else {
            $statusCode = '500';
        }

        if (!$exception instanceof UserException) {
            $message = Craft::t('app', 'Server Error');
        } else {
            $message = $exception->getMessage();
        }

        if (Craft::$app->getRequest()->getIsSiteRequest()) {
            $prefix = Craft::$app->getConfig()->getGeneral()->errorTemplatePrefix;

            if ($this->getView()->doesTemplateExist($prefix . $statusCode)) {
                $template = $prefix . $statusCode;
            } else if ($statusCode == 503 && $this->getView()->doesTemplateExist($prefix . 'offline')) {
                $template = $prefix . 'offline';
            } else if ($this->getView()->doesTemplateExist($prefix . 'error')) {
                $template = $prefix . 'error';
            }
        }

        /** @noinspection UnSafeIsSetOverArrayInspection - FP */
        if (!isset($template)) {
            $view = $this->getView();
            $view->setTemplateMode($view::TEMPLATE_MODE_CP);

            if ($view->doesTemplateExist($statusCode)) {
                $template = $statusCode;
            } else {
                $template = 'error';
            }
        }

        $variables = array_merge([
            'message' => $message,
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ], get_object_vars($exception));

        // If this is a PHP error and html_errors (http://php.net/manual/en/errorfunc.configuration.php#ini.html-errors)
        // is enabled, then allow the HTML not get encoded
        if ($exception instanceof ErrorException && App::phpConfigValueAsBool('html_errors')) {
            $variables['message'] = Template::raw($variables['message']);
        }

        return $this->renderTemplate($template, $variables);
    }
}
