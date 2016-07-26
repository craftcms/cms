<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web;

use Craft;
use craft\app\helpers\StringHelper;
use yii\db\Exception as DbException;
use yii\log\FileTarget;
use yii\web\HttpException;

/**
 * Class ErrorHandler
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ErrorHandler extends \yii\web\ErrorHandler
{
    // Properties
    // =========================================================================

    /**
     * @var boolean Whether [[renderCallStackItem()]] should render subsequent stack trace items in the event of a Twig error
     */
    private $_renderAllCallStackItems;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function handleException($exception)
    {
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

        // Log MySQL deadlocks
        if ($exception instanceof DbException && StringHelper::contains($exception->getMessage(), 'Deadlock')) {
            // TODO: MySQL specific
            $data = Craft::$app->getDb()->createCommand('SHOW ENGINE INNODB STATUS')->query();
            $info = $data->read();
            $info = serialize($info);

            Craft::error('Deadlock error, innodb status: '.$info, 'system.db.CDbCommand');
        }

        parent::handleException($exception);
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
                    $templateFile = $this->exception->getTemplateFile();
                    $resolvedTemplate = Craft::$app->getView()->resolveTemplate($templateFile);

                    if ($resolvedTemplate !== false) {
                        $file = $resolvedTemplate;
                        $line = $templateLine;
                        $this->_renderAllCallStackItems = false;
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
    protected function getTypeUrl($class, $method)
    {
        $url = parent::getTypeUrl($class, $method);

        if ($url === null) {
            if (strncmp($class, '__TwigTemplate_', 15) === 0) {
                $class = 'Twig_Template';
            }

            if (strncmp($class, 'Twig_', 5) === 0) {
                $url = "http://twig.sensiolabs.org/api/master/$class.html";

                if ($method) {
                    $url .= "#method_$method";
                }
            }
        }

        return $url;
    }
}
