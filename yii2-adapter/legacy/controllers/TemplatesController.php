<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\db\Connection;
use craft\helpers\Db;
use craft\web\Application;
use craft\web\Controller;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\PHP;
use CraftCms\Cms\Support\Template;
use CraftCms\Cms\Twig\TemplateResolver;
use CraftCms\Cms\View\TemplateMode;
use ErrorException;
use Illuminate\Support\Facades\Cache;
use RequirementsChecker;
use yii\base\UserException;
use yii\web\ErrorHandler;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;
use function CraftCms\Cms\t;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * The TemplatesController class is a controller that handles various template rendering related tasks for both the
 * control panel and front-end of a Craft site.
 * Note that all actions in the controller are open to do not require an authenticated Craft session in order to execute.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class TemplatesController extends Controller
{
    /**
     * @inheritdoc
     */
    protected array|bool|int $allowAnonymous = [
        'offline' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'manual-update-notification' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'requirements-check' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'render-error' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
    ];

    /**
     * @inheritdoc
     */
    public $enableCsrfValidation = false;

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        $actionSegments = $this->request->getActionSegments();
        if (isset($actionSegments[0]) && strtolower($actionSegments[0]) === 'templates') {
            throw new ForbiddenHttpException();
        }

        if ($action->id === 'render') {
            // Allow anonymous access to the Login template even if the site is offline
            if ($this->request->getIsLoginRequest()) {
                $this->allowAnonymous = self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE;
            } elseif ($this->request->getIsSiteRequest()) {
                $this->allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;
            }
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
        if (
            (
                Cms::config()->headlessMode &&
                $this->request->getIsSiteRequest()
            ) ||
            !Path::ensurePathIsContained($template) || // avoid the Craft::warning() from View::_validateTemplateName()
            !app(TemplateResolver::class)->exists($template)
        ) {
            throw new NotFoundHttpException('Template not found: ' . $template);
        }

        // Merge any additional route params
        /** @var Application $app */
        $app = Craft::$app;
        $routeParams = $app->getUrlManager()->getRouteParams();
        unset($routeParams['template']);
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
        if ($this->request->getIsSiteRequest() && !app(TemplateResolver::class)->exists('offline')) {
            $templateMode = TemplateMode::Cp->value;
        }

        // Output the offline template
        return $this->rendertemplate('offline', [], $templateMode ?? null);
    }

    /**
     * Renders the Manual Update notification template.
     *
     * @return Response
     */
    public function actionManualUpdateNotification(): Response
    {
        $this->response->setNoCacheHeaders();
        return $this->rendertemplate('_special/dbupdate');
    }

    /**
     * @return Response|null
     * @throws ServerErrorHttpException if it's an Ajax request and the server doesn’t meet Craft’s requirements
     */
    public function actionRequirementsCheck(): ?Response
    {
        // Run the requirements checker
        $reqCheck = new RequirementsChecker();
        $dbConfig = Craft::$app->getConfig()->getDb();
        $reqCheck->dsn = $dbConfig->dsn;
        $reqCheck->dbDriver = $dbConfig->dsn ? Db::parseDsn($dbConfig->dsn, 'driver') : Connection::DRIVER_MYSQL;
        $reqCheck->dbUser = $dbConfig->user;
        $reqCheck->dbPassword = $dbConfig->password;

        $reqCheck->checkCraft();

        if ($reqCheck->result['summary']['errors'] > 0) {
            // Coming from Updater.php
            if ($this->request->getAcceptsJson()) {
                $message = '<br /><br />';

                foreach ($reqCheck->getResult()['requirements'] as $req) {
                    if ($req['error'] === true) {
                        $message .= $req['memo'] . '<br />';
                    }
                }

                throw new ServerErrorHttpException(t('The update can’t be installed :( {message}', ['message' => $message]));
            }

            return $this->rendertemplate('_special/cantrun', [
                'reqCheck' => $reqCheck,
            ]);
        }

        // Cache the base path.
        Cache::put('basePath', Craft::$app->getBasePath(), Cms::config()->cacheDuration);

        return null;
    }

    /**
     * Renders an error template.
     *
     * @return Response
     */
    public function actionRenderError(): Response
    {
        /** @var ErrorHandler $errorHandler */
        $errorHandler = Craft::$app->getErrorHandler();
        $exception = $errorHandler->exception;

        if ($exception instanceof HttpException && $exception->statusCode) {
            $statusCode = (string)$exception->statusCode;
        } else {
            $statusCode = '500';
        }

        if (!$exception instanceof UserException) {
            $message = t('Server Error');
        } else {
            $message = $exception->getMessage();
        }

        if ($this->request->getIsSiteRequest()) {
            $prefix = Cms::config()->errorTemplatePrefix;
            $resolver = app(TemplateResolver::class);

            if ($resolver->exists($prefix . $statusCode)) {
                $template = $prefix . $statusCode;
            } elseif ($statusCode == 503 && $resolver->exists($prefix . 'offline')) {
                $template = $prefix . 'offline';
            } elseif ($resolver->exists($prefix . 'error')) {
                $template = $prefix . 'error';
            }
        }

        /** @noinspection UnSafeIsSetOverArrayInspection - FP */
        if (!isset($template)) {
            TemplateMode::set(TemplateMode::Cp);

            if (app(TemplateResolver::class)->exists($statusCode)) {
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
            'statusCode' => $statusCode,
        ], get_object_vars($exception));

        // If this is a PHP error and html_errors (https://php.net/manual/en/errorfunc.configuration.php#ini.html-errors)
        // is enabled, then allow the HTML not get encoded
        if ($exception instanceof ErrorException && PHP::configValueAsBool('html_errors')) {
            $variables['message'] = Template::raw($variables['message']);
        }

        return $this->renderTemplate($template, $variables);
    }
}
