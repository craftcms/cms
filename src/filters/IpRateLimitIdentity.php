<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\filters;

use Craft;
use yii\filters\RateLimitInterface;
use yii\web\Request;

/**
 * IP-based rate limit identity for use with RateLimiter.
 *
 * This class implements RateLimitInterface to provide IP-based rate limiting
 * for unauthenticated requests, using cache storage for allowance tracking.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.x
 */
class IpRateLimitIdentity implements RateLimitInterface
{
    /**
     * Constructor.
     *
     * @param int $limit Maximum number of requests allowed within the window
     * @param int $window Time window in seconds
     * @param string $keyPrefix Cache key prefix for storing allowance data
     * @param string $ip The IP address to rate limit
     */
    public function __construct(
        private int $limit,
        private int $window,
        private string $keyPrefix,
        private string $ip,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getRateLimit($request, $action): array
    {
        return [$this->limit, $this->window];
    }

    /**
     * @inheritdoc
     */
    public function loadAllowance($request, $action): array
    {
        $key = $this->getCacheKey($action);
        $data = Craft::$app->getCache()->get($key);
        return $data !== false ? $data : [$this->limit, time()];
    }

    /**
     * @inheritdoc
     */
    public function saveAllowance($request, $action, $allowance, $timestamp): void
    {
        $key = $this->getCacheKey($action);
        Craft::$app->getCache()->set($key, [$allowance, $timestamp], $this->window);
    }

    /**
     * Generates the cache key for storing rate limit allowance.
     *
     * @param mixed $action The action being rate limited
     * @return string The cache key
     */
    private function getCacheKey($action): string
    {
        return sprintf('%s:%s:%s', $this->keyPrefix, $action->getUniqueId(), $this->ip);
    }
}
