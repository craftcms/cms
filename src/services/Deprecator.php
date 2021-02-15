<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\db\ElementQuery;
use craft\errors\DeprecationException;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use craft\models\DeprecationError;
use craft\web\twig\Extension;
use Twig\Template as TwigTemplate;
use yii\base\Application;
use yii\base\Component;
use yii\db\Exception;

/**
 * Deprecator service.
 * An instance of the Deprecator service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getDeprecator()|`Craft::$app->deprecator`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Deprecator extends Component
{
    /**
     * @var bool Whether deprecation warnings should throw exceptions rather than being logged.
     * @since 3.1.18
     */
    public $throwExceptions = false;

    /**
     * @var string|false Whether deprecation errors should be logged in the database ('db'),
     * error logs ('logs'), or not at all (false).
     *
     * Changing this will prevent deprecation errors from showing up in the "Deprecation Warnings" utility
     * or in the "Deprecated" panel in the Debug Toolbar.
     *
     * @since 3.0.7
     */
    public $logTarget = 'db';

    /**
     * @var DeprecationError[] The deprecation errors that were logged in the current request
     */
    private $_requestLogs = [];

    /**
     * @var DeprecationError[]|null All the unique deprecation errors that have been logged
     */
    private $_allLogs;

    /**
     * @inheritdoc
     * @since 3.4.12
     */
    public function init()
    {
        if (!$this->throwExceptions && $this->logTarget === 'db') {
            Craft::$app->on(Application::EVENT_AFTER_REQUEST, [$this, 'storeLogs'], null, false);
        }

        parent::init();
    }

    /**
     * Logs a new deprecation error.
     *
     * @param string $key
     * @param string $message
     * @param string|null $file
     * @param int|null $line
     * @throws DeprecationException
     */
    public function log(string $key, string $message, string $file = null, int $line = null)
    {
        if ($this->logTarget === false) {
            return;
        }

        // Get the debug backtrace
        $traces = debug_backtrace();

        if ($file === null) {
            [$file, $line] = $this->_findOrigin($traces);
        }

        if ($this->throwExceptions) {
            throw new DeprecationException($message, $file, $line);
        }

        $fingerprint = $file . ($line ? ':' . $line : '');

        // Don't log the same key/fingerprint twice in the same request
        $this->_requestLogs["$key-$fingerprint"] = new DeprecationError([
            'key' => $key,
            'fingerprint' => $fingerprint,
            'lastOccurrence' => new \DateTime(),
            'file' => $file,
            'line' => $line,
            'message' => $message,
            'traces' => $this->_cleanTraces($traces)
        ]);
    }

    /**
     * Stores all the deprecation warnings that were logged in this request.
     *
     * @since 3.4.12
     */
    public function storeLogs()
    {
        $db = Craft::$app->getDb();

        foreach ($this->_requestLogs as $log) {
            try {
                Db::upsert(Table::DEPRECATIONERRORS, [
                    'key' => $log->key,
                    'fingerprint' => $log->fingerprint,
                ], [
                    'lastOccurrence' => Db::prepareDateForDb($log->lastOccurrence),
                    'file' => $log->file,
                    'line' => $log->line,
                    'message' => $log->message,
                    'traces' => Json::encode($log->traces),
                ]);
                $log->id = $db->getLastInsertID();
            } catch (Exception $e) {
                Craft::warning("Couldn't save deprecation warning: {$e->getMessage()}", __METHOD__);
                // Craft probably isn’t installed yet
                break;
            }
        }
    }

    /**
     * Returns the deprecation errors that were logged in the current request.
     *
     * @return DeprecationError[]
     */
    public function getRequestLogs(): array
    {
        return $this->_requestLogs;
    }

    /**
     * Returns the total number of deprecation errors that have been logged.
     *
     * @return int
     */
    public function getTotalLogs(): int
    {
        return (new Query())
            ->from([Table::DEPRECATIONERRORS])
            ->count('[[id]]');
    }

    /**
     * Get 'em all.
     *
     * @param int|null $limit
     * @return DeprecationError[]
     */
    public function getLogs(int $limit = null): array
    {
        if ($this->_allLogs !== null) {
            return $this->_allLogs;
        }

        $this->_allLogs = [];

        $results = $this->_createDeprecationErrorQuery()
            ->limit($limit)
            ->orderBy(['lastOccurrence' => SORT_DESC])
            ->all();

        foreach ($results as $result) {
            $this->_allLogs[] = new DeprecationError($result);
        }

        return $this->_allLogs;
    }

    /**
     * Returns a log by its ID.
     *
     * @param int $logId
     * @return DeprecationError|null
     */
    public function getLogById(int $logId)
    {
        $log = $this->_createDeprecationErrorQuery()
            ->where(['id' => $logId])
            ->one();

        return $log ? new DeprecationError($log) : null;
    }

    /**
     * Deletes a log by its ID.
     *
     * @param int $id
     * @return bool
     */
    public function deleteLogById(int $id): bool
    {
        $affectedRows = Db::delete(Table::DEPRECATIONERRORS, [
            'id' => $id,
        ]);

        return (bool)$affectedRows;
    }

    /**
     * Deletes all logs.
     *
     * @return bool
     */
    public function deleteAllLogs(): bool
    {
        $affectedRows = Db::delete(Table::DEPRECATIONERRORS);

        return (bool)$affectedRows;
    }

    /**
     * Returns a Query object prepped for retrieving deprecation logs.
     *
     * @return Query
     */
    private function _createDeprecationErrorQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'key',
                'fingerprint',
                'lastOccurrence',
                'file',
                'line',
                'message',
                'traces',
            ])
            ->from([Table::DEPRECATIONERRORS]);
    }

    /**
     * Returns the file and line number that should be associated with the error.
     *
     * @param array $traces debug_backtrace() results leading up to [[log()]]
     * @return array [file, line]
     */
    private function _findOrigin(array $traces): array
    {
        // Should we be treating this as as template deprecation log?
        if (empty($traces[2]['class']) && isset($traces[2]['function']) && $traces[2]['function'] === 'twig_get_attribute') {
            // came through twig_get_attribute()
            $templateTrace = 3;
        } else if ($this->_isTemplateAttributeCall($traces, 4)) {
            // came through Template::attribute()
            $templateTrace = 4;
        } else if ($this->_isTemplateAttributeCall($traces, 2)) {
            // special case for "deprecated" date functions the Template helper pretends still exist
            $templateTrace = 2;
        } else if (
            isset($traces[1]['class'], $traces[1]['function']) &&
            (
                ($traces[1]['class'] === ElementQuery::class && $traces[1]['function'] === 'getIterator') ||
                (
                    $traces[1]['class'] === Extension::class &&
                    in_array($traces[1]['function'], [
                        'getCsrfInput',
                        'getFootHtml',
                        'getHeadHtml',
                        'groupFilter',
                        'roundFunction',
                        'svgFunction',
                        'ucwordsFilter',
                    ], true)
                )
            )
        ) {
            // special case for deprecated looping through element queries
            $templateTrace = 1;
        }

        if (isset($templateTrace)) {
            $templateCodeLine = $traces[$templateTrace]['line'] ?? null;
            $template = $traces[$templateTrace + 1]['object'] ?? null;

            if ($template instanceof TwigTemplate) {
                $templateName = $template->getTemplateName();
                $file = Craft::$app->getView()->resolveTemplate($templateName) ?: $templateName;
                $line = $this->_findTemplateLine($template, $templateCodeLine);
                return [$file, $line];
            }
        }

        // Did this go through Component::__get()?
        if (isset($traces[2]['class'], $traces[2]['function']) && $traces[2]['class'] === Component::class && $traces[2]['function'] === '__get') {
            $t = 3;
        } else {
            $t = 1;
        }

        $file = $traces[$t]['file'] ?? '';
        $line = $traces[$t]['line'] ?? null;

        return [$file, $line];
    }

    /**
     * Returns whether the given trace is a call to [[\craft\heplers\Template::attribute()]]
     *
     * @param array $traces debug_backtrace() results leading up to [[log()]]
     * @param int $index The trace index to check
     * @return bool
     */
    private function _isTemplateAttributeCall(array $traces, int $index): bool
    {
        if (!isset($traces[$index])) {
            return false;
        }
        $t = $traces[$index];
        return (
            isset($t['class'], $t['function']) &&
            $t['class'] === Template::class &&
            $t['function'] === 'attribute'
        );
    }

    /**
     * Returns a simplified version of the stack trace.
     *
     * @param array $traces debug_backtrace() results leading up to [[log()]]
     * @return array
     */
    private function _cleanTraces(array $traces): array
    {
        $logTraces = [];

        foreach ($traces as $i => $trace) {
            $logTraces[] = [
                'objectClass' => !empty($trace['object']) ? get_class($trace['object']) : null,
                'file' => !empty($trace['file']) ? $trace['file'] : null,
                'line' => !empty($trace['line']) ? $trace['line'] : null,
                'class' => !empty($trace['class']) ? $trace['class'] : null,
                'method' => !empty($trace['function']) ? $trace['function'] : null,
                'args' => !empty($trace['args']) ? $this->_argsToString($trace['args']) : null,
            ];
        }

        return $logTraces;
    }

    /**
     * Returns the Twig template that should be associated with the deprecation error, if any.
     *
     * @param TwigTemplate $template
     * @param int|null $actualCodeLine
     * @return int|null
     */
    private function _findTemplateLine(TwigTemplate $template, int $actualCodeLine = null)
    {
        if ($actualCodeLine === null) {
            return null;
        }

        // getDebugInfo() goes upward, so the first code line that's <= the trace line will be the match
        foreach ($template->getDebugInfo() as $codeLine => $templateLine) {
            if ($codeLine <= $actualCodeLine) {
                return $templateLine;
            }
        }

        return null;
    }

    /**
     * Converts an array of method arguments to a string.
     *
     * Adapted from [[\yii\web\ErrorHandler::argumentsToString()]], but this one's less destructive
     *
     * @param array $args
     * @return string
     */
    private function _argsToString(array $args): string
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
                    $strValue = '"' . StringHelper::substr($value, 0, 64) . '..."';
                } else {
                    $strValue = '"' . $value . '"';
                }
            } else if (is_array($value)) {
                $strValue = '[' . $this->_argsToString($value) . ']';
            } else if ($value === null) {
                $strValue = 'null';
            } else if (is_resource($value)) {
                $strValue = 'resource';
            } else {
                $strValue = $value;
            }

            if (is_string($key)) {
                $strArgs[] = '"' . $key . '" => ' . $strValue;
            } else if ($isAssoc) {
                $strArgs[] = $key . ' => ' . $strValue;
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
