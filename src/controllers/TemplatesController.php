<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\helpers\App;
use craft\app\helpers\Template;
use craft\app\web\Controller;
use ErrorException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * The TemplatesController class is a controller that handles various template rendering related tasks for both the
 * control panel and front-end of a Craft site.
 *
 * Note that all actions in the controller are open to do not require an authenticated Craft session in order to execute.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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
     * Renders a template.
     *
     * @param       $template
     * @param array $variables
     *
     * @return string The rendering result
     * @throws NotFoundHttpException if the requested template cannot be found
     */
    public function actionRender($template, array $variables = [])
    {
        // Does that template exist?
        if (Craft::$app->getView()->doesTemplateExist($template)) {
            return $this->renderTemplate($template, $variables);
        }

        throw new NotFoundHttpException('Template not found');
    }

    /**
     * Shows the 'offline' template.
     *
     * @return string The rendering result
     */
    public function actionOffline()
    {
        // If this is a site request, make sure the offline template exists
        $view = Craft::$app->getView();
        if (Craft::$app->getRequest()->getIsSiteRequest() && !$view->doesTemplateExist('offline')) {
            $view->setTemplateMode($view::TEMPLATE_MODE_CP);
        }

        // Output the offline template
        return $this->renderTemplate('offline');
    }

    /**
     * Renders the Manual Update notification template.
     *
     * @return string The rendering result
     */
    public function actionManualUpdateNotification()
    {
        return $this->renderTemplate('_special/dbupdate');
    }

    /**
     * Renders the Manual Update template.
     *
     * @return string The rendering result
     */
    public function actionManualUpdate()
    {
        return $this->renderTemplate('updates/_go', [
            'handle' => Craft::$app->getRequest()->getSegment(2)
        ]);
    }

    /**
     * @return string The rendering result
     * @throws ServerErrorHttpException if it's an Ajax request and the server doesn’t meet Craft’s requirements
     */
    public function actionRequirementsCheck()
    {
        require_once(Craft::$app->getPath()->getAppPath().'/requirements/RequirementsChecker.php');

        // Run the requirements checker
        $reqCheck = new \RequirementsChecker();
        $reqCheck->checkCraft();

        if ($reqCheck->result['summary']['errors'] > 0) {
            // Coming from Updater.php
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                $message = '<br /><br />';

                foreach ($reqCheck->getResult()['requirements'] as $req) {
                    if ($req['failed'] === true) {
                        $message .= $req['memo'].'<br />';
                    }
                }

                throw new ServerErrorHttpException(Craft::t('app', 'The update can’t be installed :( {message}', ['message' => $message]));
            } else {
                return $this->renderTemplate('_special/cantrun',
                    ['reqCheck' => $reqCheck]);
            }
        } else {
            // Cache the app path.
            Craft::$app->getCache()->set('appPath', Craft::$app->getPath()->getAppPath());
        }

        return null;
    }

    /**
     * Renders an error template.
     *
     * @return string
     */
    public function actionRenderError()
    {
        /** @var $errorHandler \yii\web\ErrorHandler */
        $errorHandler = Craft::$app->getErrorHandler();
        $exception = $errorHandler->exception;

        if ($exception instanceof HttpException && $exception->statusCode) {
            $statusCode = (string)$exception->statusCode;
        } else {
            $statusCode = '500';
        }

        if (Craft::$app->getRequest()->getIsSiteRequest()) {
            $prefix = Craft::$app->getConfig()->get('errorTemplatePrefix');

            if (Craft::$app->getView()->doesTemplateExist($prefix.$statusCode)) {
                $template = $prefix.$statusCode;
            } else if ($statusCode == 503 && Craft::$app->getView()->doesTemplateExist($prefix.'offline')) {
                $template = $prefix.'offline';
            } else if (Craft::$app->getView()->doesTemplateExist($prefix.'error')) {
                $template = $prefix.'error';
            }
        }

        if (!isset($template)) {
            $view = Craft::$app->getView();
            $view->setTemplateMode($view::TEMPLATE_MODE_CP);

            if ($view->doesTemplateExist($statusCode)) {
                $template = $statusCode;
            } else {
                $template = 'error';
            }
        }

        $variables = array_merge([
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ], get_object_vars($exception));

        // If this is a PHP error and html_errors (http://php.net/manual/en/errorfunc.configuration.php#ini.html-errors)
        // is enabled, then allow the HTML not get encoded
        if ($exception instanceof ErrorException && App::getPhpConfigValueAsBool('html_errors')) {
            $variables['message'] = Template::getRaw($variables['message']);
        }

        return $this->renderTemplate($template, $variables);
    }
}
