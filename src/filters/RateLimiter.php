<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\filters;

use Craft;
use yii\filters\RateLimiter as YiiRateLimiter;

/**
 * IP-based rate limiter filter.
 *
 * This filter extends Yii's RateLimiter to provide IP-based rate limiting
 * for unauthenticated requests. It uses an [[IpRateLimitIdentity]] instance
 * to track request allowances in the cache.
 *
 * Example usage in a controller's `behaviors()` method:
 *
 * ```php
 * public function behaviors(): array
 * {
 *     return [
 *         'rateLimiter' => [
 *             'class' => \craft\filters\RateLimiter::class,
 *             'only' => ['send-password-reset-email'],
 *             'limit' => 1,
 *             'window' => 1,
 *         ],
 *     ];
 * }
 * ```
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.x
 */
class RateLimiter extends YiiRateLimiter
{
    /**
     * @inheritdoc
     */
    public $enableRateLimitHeaders = false;

    /**
     * @var int Maximum number of requests allowed within the window
     */
    public int $limit = 1;

    /**
     * @var int Time window in seconds
     */
    public int $window = 1;

    /**
     * @var string Cache key prefix for storing allowance data
     */
    public string $keyPrefix = 'ratelimit';

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->user = fn() => new IpRateLimitIdentity(
            $this->limit,
            $this->window,
            $this->keyPrefix,
            Craft::$app->getRequest()->getUserIP() ?? 'unknown',
        );
    }
}
