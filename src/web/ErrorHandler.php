<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\events\ExceptionEvent;
use yii\base\UserException;
use yii\log\FileTarget;
use yii\web\HttpException;

/**
 * Class ErrorHandler
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ErrorHandler extends \yii\web\ErrorHandler
{
    // Constants
    // =========================================================================

    /**
     * @event ExceptionEvent The event that is triggered before handling an exception.
     */
    const EVENT_BEFORE_HANDLE_EXCEPTION = 'beforeHandleException';

    // Properties
    // =========================================================================

    /**
     * @var bool|null Whether [[renderCallStackItem()]] should render subsequent stack trace items in the event of a Twig error
     */
    private $_renderAllCallStackItems;

    // Public Methods
    // =========================================================================

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
        if ($exception instanceof \Twig_Error_Runtime && ($previousException = $exception->getPrevious()) !== null) {
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
        if ($exception instanceof \Twig_Error) {
            if ($exception instanceof \Twig_Error_Syntax) {
                return 'Twig Syntax Error';
            }

            if ($exception instanceof \Twig_Error_Loader) {
                return 'Twig Template Loading Error';
            }

            if ($exception instanceof \Twig_Error_Runtime) {
                return 'Twig Runtime Error';
            }
        }

        return parent::getExceptionName($exception);
    }

    /**
     * @inheritdoc
     */
    public function renderCallStackItem($file, $line, $class, $method, $args, $index)
    {
        // Special behavior for Twig errors
        if ($this->exception instanceof \Twig_Error) {
            if ($index === 1) {
                $this->_renderAllCallStackItems = true;
                $templateLine = $this->exception->getTemplateLine();

                // $templateLine could be null or -1
                if (is_int($templateLine) && $templateLine > 0) {
                    $templateSource = $this->exception->getSourceContext();
                    if ($templateSource !== null) {
                        $templateFile = $templateSource->getName();
                        $resolvedTemplate = Craft::$app->getView()->resolveTemplate($templateFile);
                        if ($resolvedTemplate !== false) {
                            $file = $resolvedTemplate;
                            $line = $templateLine;
                            $this->_renderAllCallStackItems = false;
                        }
                    }
                }
            } else if ($this->_renderAllCallStackItems === false) {
                return null;
            }
        }

        return parent::renderCallStackItem($file, $line, $class, $method, $args, $index);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function renderException($exception)
    {
        // Treat UserExceptions like normal exceptions when Dev Mode is enabled
        if (YII_DEBUG && $exception instanceof UserException) {
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
                $class = \Twig_Template::class;
            }

            if (strpos($class, 'Twig_') === 0) {
                $url = "http://twig.sensiolabs.org/api/2.x/$class.html";

                if ($method) {
                    $url .= "#method_$method";
                }
            }
        }

        return $url;
    }
}
