<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use CraftCms\Cms\Deprecator\Deprecator as DeprecatorService;
use CraftCms\Cms\Deprecator\Exceptions\DeprecationException;
use CraftCms\Cms\Deprecator\Models\DeprecationError;
use yii\base\Component;

/**
 * Deprecator service.
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getDeprecator()|`Craft::$app->getDeprecator()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Deprecator\Deprecator} instead.
 */
class Deprecator extends Component
{
    /**
     * @var bool Whether deprecation warnings should throw exceptions rather than being logged.
     * @since 3.1.18
     */
    public bool $throwExceptions = false;

    /**
     * @var string|false Whether deprecation warnings should be logged in the database ('db'),
     * error logs ('logs'), or not at all (false).
     *
     * Changing this will prevent deprecation warnings from showing up in the "Deprecation Warnings" utility
     * or in the "Deprecated" panel in the Debug Toolbar.
     *
     * @since 3.0.7
     */
    public string|false $logTarget = 'db';

    /**
     * @inheritdoc
     * @since 3.4.12
     */
    public function init(): void
    {
        parent::init();

        DeprecatorService::$throwExceptions = $this->throwExceptions;
        DeprecatorService::$logTarget = $this->logTarget;
    }

    /**
     * Logs a new deprecation error.
     *
     * @param string $key
     * @param string $message
     * @param string|null $file
     * @param int|null $line
     *
     * @throws DeprecationException
     */
    public function log(string $key, string $message, ?string $file = null, ?int $line = null): void
    {
        app(DeprecatorService::class)->log($key, $message, $file, $line);
    }

    /**
     * Stores all the deprecation warnings that were logged in this request.
     *
     * @since 3.4.12
     */
    public function storeLogs(): void
    {
        app(DeprecatorService::class)->storeLogs();
    }

    /**
     * Returns the deprecation warnings that were logged in the current request.
     *
     * @return DeprecationError[]
     */
    public function getRequestLogs(): array
    {
        return app(DeprecatorService::class)->getRequestLogs();
    }

    /**
     * Returns the total number of deprecation warnings that have been logged.
     *
     * @return int
     */
    public function getTotalLogs(): int
    {
        return app(DeprecatorService::class)->getTotalLogs();
    }

    /**
     * Get 'em all.
     *
     * @param int|null $limit
     *
     * @return DeprecationError[]
     */
    public function getLogs(?int $limit = null): array
    {
        return app(DeprecatorService::class)->getLogs($limit);
    }

    /**
     * Returns a log by its ID.
     *
     * @param int $logId
     *
     * @return DeprecationError|null
     */
    public function getLogById(int $logId): ?DeprecationError
    {
        return app(DeprecatorService::class)->getLogById($logId);
    }

    /**
     * Deletes a log by its ID.
     *
     * @param int $id
     *
     * @return bool
     */
    public function deleteLogById(int $id): bool
    {
        return app(DeprecatorService::class)->deleteLogById($id);
    }

    /**
     * Deletes all logs.
     *
     * @return bool
     */
    public function deleteAllLogs(): bool
    {
        return app(DeprecatorService::class)->deleteAllLogs();
    }
}
