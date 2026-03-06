<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\filters;

use Craft;
use yii\base\Action;
use yii\base\BaseObject;
use yii\filters\RateLimitInterface;

/**
 * IP-based rate limit identity for use with RateLimiter.
 *
 * This class implements RateLimitInterface to provide IP-based rate limiting
 * for unauthenticated requests, using cache storage for allowance tracking.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.9.15
 */
class IpRateLimitIdentity extends BaseObject implements RateLimitInterface
{
    /**
     * @var int Maximum number of requests allowed within the window
     */
    public int $limit;

    /**
     * @var int Time window in seconds
     */
    public int $window;

    /**
     * @var string Cache key prefix for storing allowance data
     */
    public string $keyPrefix;

    /**
     * @var string The IP address to rate limit
     */
    public string $ip;

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
     */
    private function getCacheKey(Action $action): string
    {
        return sprintf('%s:%s:%s', $this->keyPrefix, $action->getUniqueId(), $this->ip);
    }
}
