<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\events\ExceptionEvent;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\log\Dispatcher;
use GuzzleHttp\Exception\ClientException;
use Twig\Error\Error as TwigError;
use Twig\Error\LoaderError as TwigLoaderError;
use Twig\Error\RuntimeError as TwigRuntimeError;
use Twig\Error\SyntaxError as TwigSyntaxError;
use Twig\Template as TwigTemplate;
use yii\base\UserException;
use yii\log\FileTarget;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;

/**
 * Class ErrorHandler
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ErrorHandler extends \yii\web\ErrorHandler
{
    /**
     * @event ExceptionEvent The event that is triggered before handling an exception.
     */
    const EVENT_BEFORE_HANDLE_EXCEPTION = 'beforeHandleException';

    /**
     * @inheritdoc
     */
    public function handleException($exception)
    {
        // Fire a 'beforeHandleException' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_HANDLE_EXCEPTION)) {
            $this->trigger(self::EVENT_BEFORE_HANDLE_EXCEPTION, new ExceptionEvent([
                'exception' => $exception,
            ]));
        }

        // If this is a Twig Runtime exception, use the previous one instead
        if ($exception instanceof TwigRuntimeError && ($previousException = $exception->getPrevious()) !== null) {
            $exception = $previousException;
        }

        // 404?
        if ($exception instanceof HttpException && $exception->statusCode === 404) {
            // Log to a special file
            $logDispatcher = Craft::$app->getLog();
            $fileTarget = $logDispatcher->targets[Dispatcher::TARGET_FILE] ?? $logDispatcher->targets[0] ?? null;
            if ($fileTarget && $fileTarget instanceof FileTarget) {
                $fileTarget->logFile = Craft::getAlias('@storage/logs/web-404s.log');
            }

            $request = Craft::$app->getRequest();
            if ($request->getIsSiteRequest() && $request->getPathInfo() === 'wp-admin') {
                $exception->statusCode = 418;
            }
        }

        parent::handleException($exception);
    }

    /**
     * @inheritdoc
     */
    public function handleError($code, $message, $file, $line)
    {
        // Because: https://bugs.php.net/bug.php?id=74980
        if (PHP_VERSION_ID >= 70100 && strpos($message, 'Narrowing occurred during type inference. Please file a bug report') !== false) {
            return null;
        }

        return parent::handleError($code, $message, $file, $line);
    }

    /**
     * @inheritdoc
     */
    public function getExceptionName($exception)
    {
        // Yii isn't translating its own exceptions' names, so meh
        if ($exception instanceof TwigError) {
            if ($exception instanceof TwigSyntaxError) {
                return 'Twig Syntax Error';
            }

            if ($exception instanceof TwigLoaderError) {
                return 'Twig Template Loading Error';
            }

            if ($exception instanceof TwigRuntimeError) {
                return 'Twig Runtime Error';
            }
        }

        return parent::getExceptionName($exception);
    }

    /**
     * @inheritdoc
     */
    public function isCoreFile($file)
    {
        if (parent::isCoreFile($file)) {
            return true;
        }

        $file = realpath($file);
        $pathService = Craft::$app->getPath();
        return strpos($file, $pathService->getCompiledTemplatesPath() . DIRECTORY_SEPARATOR) === 0 ||
            strpos($file, $pathService->getVendorPath() . DIRECTORY_SEPARATOR . 'twig' . DIRECTORY_SEPARATOR . 'twig' . DIRECTORY_SEPARATOR) === 0 ||
            $file === __DIR__ . DIRECTORY_SEPARATOR . 'twig' . DIRECTORY_SEPARATOR . 'Template.php';
    }

    /**
     * @inheritdoc
     */
    protected function renderException($exception)
    {
        $request = Craft::$app->has('request', true) ? Craft::$app->getRequest() : null;
        $response = Craft::$app->getResponse();

        // Return JSON for JSON requests
        if ($request && $request->getAcceptsJson()) {
            $response->format = Response::FORMAT_JSON;
            if ($this->_showExceptionView()) {
                $response->data = [
                    'error' => $exception->getMessage(),
                    'exception' => get_class($exception),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => array_map(function($step) {
                        unset($step['args']);
                        return $step;
                    }, $exception->getTrace()),
                ];
            } else {
                $response->data = [
                    'error' => $exception instanceof UserException ? $exception->getMessage() : Craft::t('app', 'A server error occurred.'),
                ];
            }

            // Override the status code and error message if this is a Guzzle client exception
            if ($exception instanceof ClientException) {
                $response->setStatusCode($exception->getCode());
                if (($guzzleResponse = $exception->getResponse()) !== null) {
                    $body = Json::decodeIfJson((string)$guzzleResponse->getBody());
                    if (isset($body['message'])) {
                        $response->data['error'] = $body['message'];
                    }
                }
            } else {
                $response->setStatusCodeByException($exception);
            }

            $response->send();
            return;
        }

        // Set the response format to HTML if it's still set to raw
        if ($response->format === Response::FORMAT_RAW) {
            $response->format = Response::FORMAT_HTML;
        }

        // Show a broken image for image requests
        if (
            $exception instanceof NotFoundHttpException &&
            $request &&
            $request->getAcceptsImage() &&
            Craft::$app->getConfig()->getGeneral()->brokenImagePath
        ) {
            $this->errorAction = 'app/broken-image';
        }
        // Show the full exception view for all exceptions when Dev Mode is enabled (don't skip `UserException`s)
        // or if the user is an admin and has indicated they want to see it
        elseif ($this->_showExceptionView()) {
            $this->errorAction = null;
            $this->errorView = $this->exceptionView;
        }

        parent::renderException($exception);
    }

    /**
     * @inheritdoc
     */
    protected function getTypeUrl($class, $method)
    {
        $url = parent::getTypeUrl($class, $method);

        if ($url === null) {
            if (strpos($class, '__TwigTemplate_') === 0) {
                $class = TwigTemplate::class;
            }

            if (strpos($class, 'Twig\\') === 0) {
                $url = "http://twig.sensiolabs.org/api/2.x/$class.html";

                if ($method) {
                    $url .= "#method_$method";
                }
            }
        }

        return $url;
    }

    /**
     * @inheritdoc
     */
    public function renderCallStackItem($file, $line, $class, $method, $args, $index)
    {
        $templateInfo = Template::resolveTemplatePathAndLine($file ?? '', $line);

        if ($templateInfo !== false) {
            [$file, $line] = $templateInfo;
        }

        return parent::renderCallStackItem($file, $line, $class, $method, $args, $index);
    }

    /**
     * Returns whether the full exception view should be shown.
     *
     * @return bool
     */
    private function _showExceptionView(): bool
    {
        if (YII_DEBUG) {
            return true;
        }

        $user = Craft::$app->getUser()->getIdentity();
        return (
            $user &&
            $user->admin &&
            $user->getPreference('showExceptionView')
        );
    }

    /**
     * @inheritdoc
     * @since 3.4.10
     */
    protected function shouldRenderSimpleHtml()
    {
        return YII_ENV_TEST || (Craft::$app->has('request', true) && Craft::$app->request->getIsAjax());
    }
}
