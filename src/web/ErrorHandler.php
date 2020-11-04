<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\events\ExceptionEvent;
use Twig\Error\Error as TwigError;
use Twig\Error\LoaderError as TwigLoaderError;
use Twig\Error\RuntimeError as TwigRuntimeError;
use Twig\Error\SyntaxError as TwigSyntaxError;
use Twig\Template;
use yii\base\Exception;
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
                'exception' => $exception
            ]));
        }

        // If this is a Twig Runtime exception, use the previous one instead
        if ($exception instanceof TwigRuntimeError && ($previousException = $exception->getPrevious()) !== null) {
            $exception = $previousException;
        }

        // If this is a 404 error, log to a special file
        if ($exception instanceof HttpException && $exception->statusCode === 404) {
            $logDispatcher = Craft::$app->getLog();

            if (isset($logDispatcher->targets[0]) && $logDispatcher->targets[0] instanceof FileTarget) {
                /** @var FileTarget $logTarget */
                $logTarget = $logDispatcher->targets[0];
                $logTarget->logFile = Craft::getAlias('@storage/logs/web-404s.log');
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
        // Set the response format back to HTML if it's still set to raw
        if (Craft::$app->has('response')) {
            $response = Craft::$app->getResponse();
            if ($response->format === Response::FORMAT_RAW) {
                $response->format = Response::FORMAT_HTML;
            }
        }

        // Show a broken image for image requests
        if (
            $exception instanceof NotFoundHttpException &&
            Craft::$app->has('request') &&
            Craft::$app->getRequest()->getAcceptsImage() &&
            Craft::$app->getConfig()->getGeneral()->brokenImagePath
        ) {
            $this->errorAction = 'app/broken-image';
        }
        // Show the full exception view for all exceptions when Dev Mode is enabled (don't skip `UserException`s)
        // or if the user is an admin and has indicated they want to see it
        else if ($this->_showExceptionView()) {
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
                $class = Template::class;
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
        if (strpos($file, 'compiled_templates') !== false) {
            try {
                list($file, $line) = $this->_resolveTemplateTrace($file, $line);
            } catch (\Throwable $e) {
                // oh well, we tried
            }
        }

        return parent::renderCallStackItem($file, $line, $class, $method, $args, $index);
    }

    /**
     * Attempts to swap out debug trace info with template info.
     *
     * @throws \Throwable
     */
    private function _resolveTemplateTrace(string $traceFile, int $traceLine = null)
    {
        $contents = file_get_contents($traceFile);
        if (!preg_match('/^class (\w+)/m', $contents, $match)) {
            throw new Exception("Unable to determine template class in $traceFile");
        }
        $class = $match[1];
        /** @var Template $template */
        $template = new $class(Craft::$app->getView()->getTwig());
        $src = $template->getSourceContext();
        //                $this->sourceCode = $src->getCode();
        $file = $src->getPath();
        $line = null;

        if ($traceLine !== null) {
            foreach ($template->getDebugInfo() as $codeLine => $templateLine) {
                if ($codeLine <= $traceLine) {
                    $line = $templateLine;
                    break;
                }
            }
        }

        return [$file, $line];
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
