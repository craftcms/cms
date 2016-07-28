<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\db\Query;
use craft\app\helpers\Db;
use craft\app\helpers\Json;
use craft\app\helpers\StringHelper;
use craft\app\models\DeprecationError;
use craft\app\web\twig\Template;
use yii\base\Component;

/**
 * Class Deprecator service.
 *
 * An instance of the Deprecator service is globally accessible in Craft via [[Application::deprecator `Craft::$app->getDeprecator()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Deprecator extends Component
{
    // Properties
    // =========================================================================

    /**
     * @var string
     */
    private static $_tableName = '{{%deprecationerrors}}';

    /**
     * @var DeprecationError[] The deprecation errors that were logged in the current request
     */
    private $_requestLogs = [];

    /**
     * @var DeprecationError[] All the unique deprecation errors that have been logged
     */
    private $_allLogs;

    // Public Methods
    // =========================================================================

    /**
     * Logs a new deprecation error.
     *
     * @param string $key
     * @param string $message
     *
     * @return boolean
     */
    public function log($key, $message)
    {
        if (!Craft::$app->getIsInstalled()) {
            Craft::warning($message, 'deprecationlog');

            return false;
        }

        $request = Craft::$app->getRequest();

        $log = new DeprecationError();

        $log->key = $key;
        $log->message = $message;
        $log->lastOccurrence = new \DateTime();
        $log->template = (!$request->getIsConsoleRequest() && $request->getIsSiteRequest() ? Craft::$app->getView()->getRenderingTemplate() : null);

        // Everything else requires the stack trace
        $this->_populateLogWithStackTraceData($log);

        $index = $log->key.'-'.$log->fingerprint;

        // Don't log the same key/fingerprint twice in the same request
        if (!isset($this->_requestLogs[$index])) {
            $db = Craft::$app->getDb();

            $values = [
                'lastOccurrence' => Db::prepareDateForDb($log->lastOccurrence),
                'file' => $log->file,
                'line' => $log->line,
                'class' => $log->class,
                'method' => $log->method,
                'template' => $log->template,
                'templateLine' => $log->templateLine,
                'message' => $log->message,
                'traces' => Json::encode($log->traces),
            ];

            // Do we already have this one logged?
            $existingId = (new Query())
                ->select('id')
                ->from(static::$_tableName)
                ->where([
                    'key' => $log->key,
                    'fingerprint' => $log->fingerprint
                ])
                ->scalar();

            if ($existingId === false) {
                $db->createCommand()
                    ->insert(
                        static::$_tableName,
                        array_merge($values, [
                            'key' => $log->key,
                            'fingerprint' => $log->fingerprint
                        ]))
                    ->execute();
                $log->id = $db->getLastInsertID();
            } else {
                $db->createCommand()
                    ->update(
                        static::$_tableName,
                        $values,
                        ['id' => $existingId])
                    ->execute();
                $log->id = $existingId;
            }

            $this->_requestLogs[$key] = $log;
        }

        return true;
    }

    /**
     * Returns the deprecation errors that were logged in the current request.
     *
     * @return DeprecationError[]
     */
    public function getRequestLogs()
    {
        return $this->_requestLogs;
    }

    /**
     * Returns the total number of deprecation errors that have been logged.
     *
     * @return integer
     */
    public function getTotalLogs()
    {
        return (new Query())
            ->from(static::$_tableName)
            ->count('id');
    }

    /**
     * Get 'em all.
     *
     * @param integer $limit
     *
     * @return DeprecationError[]
     */
    public function getLogs($limit = 100)
    {
        if (!isset($this->_allLogs)) {
            $this->_allLogs = (new Query())
                ->select('*')
                ->from(static::$_tableName)
                ->limit($limit)
                ->orderBy('lastOccurrence desc')
                ->all();

            foreach ($this->_allLogs as $key => $value) {
                $this->_allLogs[$key] = DeprecationError::create($value);
            }
        }

        return $this->_allLogs;
    }

    /**
     * Returns a log by its ID.
     *
     * @param $logId
     *
     * @return DeprecationError|null
     */
    public function getLogById($logId)
    {
        $log = (new Query())
            ->select('*')
            ->from(static::$_tableName)
            ->where('id = :logId', [':logId' => $logId])
            ->one();

        if ($log !== false) {
            return DeprecationError::create($log);
        }

        return null;
    }

    /**
     * Deletes a log by its ID.
     *
     * @param $id
     *
     * @return boolean
     */
    public function deleteLogById($id)
    {
        $affectedRows = Craft::$app->getDb()->createCommand()
            ->delete(static::$_tableName, ['id' => $id])
            ->execute();

        return (bool)$affectedRows;
    }

    /**
     * Deletes all logs.
     *
     * @return boolean
     */
    public function deleteAllLogs()
    {
        $affectedRows = Craft::$app->getDb()->createCommand()
            ->delete(static::$_tableName)
            ->execute();

        return (bool)$affectedRows;
    }

    // Private Methods
    // =========================================================================

    /**
     * Populates a DeprecationError with data pulled from the PHP stack trace.
     *
     * @param DeprecationError $log
     *
     * @return void
     */
    private function _populateLogWithStackTraceData(DeprecationError $log)
    {
        // Get the stack trace, but skip the first one, since it's just the call to this private function
        $traces = debug_backtrace();
        array_shift($traces);

        // Set the basic stuff
        $log->file = $traces[0]['file'];
        $log->line = $traces[0]['line'];
        $log->class = !empty($traces[1]['class']) ? $traces[1]['class'] : null;
        $log->method = !empty($traces[1]['function']) ? $traces[1]['function'] : null;

        $request = Craft::$app->getRequest();
        $isTemplateRendering = (!$request->getIsConsoleRequest() && $request->getIsSiteRequest() && Craft::$app->getView()->getIsRenderingTemplate());

        if ($isTemplateRendering) {
            // We'll figure out the line number later
            $log->fingerprint = $log->template;

            $foundTemplate = false;
        } else {
            $log->fingerprint = $log->class.($log->class && $log->line ? ':'.$log->line : '');
        }

        $logTraces = [];

        foreach ($traces as $trace) {
            $logTrace = [
                'objectClass' => (!empty($trace['object']) ? get_class($trace['object']) : null),
                'file' => (!empty($trace['file']) ? $trace['file'] : null),
                'line' => (!empty($trace['line']) ? $trace['line'] : null),
                'class' => (!empty($trace['class']) ? $trace['class'] : null),
                'method' => (!empty($trace['function']) ? $trace['function'] : null),
                'args' => (!empty($trace['args']) ? $this->_argsToString($trace['args']) : null),
            ];

            // Is this a template?
            if (isset($trace['object']) && $trace['object'] instanceof \Twig_Template && 'Twig_Template' !== get_class($trace['object']) && isset($trace['file']) && StringHelper::contains($trace['file'], 'compiled_templates')) {
                /** @var Template $template */
                $template = $trace['object'];

                // Get the original (uncompiled) template name.
                $logTrace['template'] = $template->getTemplateName();

                // Guess the line number
                foreach ($template->getDebugInfo() as $codeLine => $templateLine) {
                    if ($codeLine <= $trace['line']) {
                        $logTrace['templateLine'] = $templateLine;

                        // Save that to the main log info too?
                        /** @noinspection PhpUndefinedVariableInspection */
                        if ($isTemplateRendering && !$foundTemplate) {
                            $log->templateLine = $templateLine;
                            $log->fingerprint .= ':'.$templateLine;
                            $foundTemplate = true;
                        }

                        break;
                    }
                }
            }

            $logTraces[] = $logTrace;
        }

        $log->traces = $logTraces;
    }

    /**
     * Converts an array of method arguments to a string.
     *
     * Adapted from [[\yii\web\ErrorHandler::argumentsToString()]], but this one's less destructive
     *
     * @param $args array
     *
     * @return string
     */
    private function _argsToString($args)
    {
        $strArgs = [];
        $isAssoc = ($args !== array_values($args));

        $count = 0;

        foreach ($args as $key => $value) {
            // Cap it off at 5
            $count++;

            if ($count == 5) {
                $strArgs[] = '...';
                break;
            }

            if (is_object($value)) {
                $strValue = get_class($value);
            } else if (is_bool($value)) {
                $strValue = $value ? 'true' : 'false';
            } else if (is_string($value)) {
                if (strlen($value) > 64) {
                    $strValue = '"'.StringHelper::substr($value, 0, 64).'..."';
                } else {
                    $strValue = '"'.$value.'"';
                }
            } else if (is_array($value)) {
                $strValue = '['.$this->_argsToString($value).']';
            } else if ($value === null) {
                $strValue = 'null';
            } else if (is_resource($value)) {
                $strValue = 'resource';
            } else {
                $strValue = $value;
            }

            if (is_string($key)) {
                $strArgs[] = '"'.$key.'" => '.$strValue;
            } else if ($isAssoc) {
                $strArgs[] = $key.' => '.$strValue;
            } else {
                $strArgs[] = $strValue;
            }

            if ($count == 5) {
                break;
            }
        }

        return implode(', ', $strArgs);
    }
}
