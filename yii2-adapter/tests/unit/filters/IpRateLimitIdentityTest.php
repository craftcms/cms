<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\filters;

use Craft;
use craft\filters\IpRateLimitIdentity;
use craft\test\TestCase;
use craft\web\Request;
use yii\base\Action;
use yii\web\Controller;

/**
 * Unit tests for IpRateLimitIdentity
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 5.x
 */
class IpRateLimitIdentityTest extends TestCase
{
    private IpRateLimitIdentity $identity;

    private Action $action;

    private Request $request;

    protected function setUp(): void
    {
        Craft::$app->getCache()->flush();

        $this->identity = new IpRateLimitIdentity([
            'limit' => 3,
            'window' => 10,
            'keyPrefix' => 'test-rate-limit',
            'ip' => '192.168.1.1',
        ]);

        $controller = $this->createMock(Controller::class);
        $this->action = new Action('test-action', $controller);
        $this->request = Craft::$app->getRequest();
    }

    public function test_get_rate_limit(): void
    {
        [$limit, $window] = $this->identity->getRateLimit($this->request, $this->action);
        self::assertSame(3, $limit);
        self::assertSame(10, $window);
    }

    public function test_load_allowance_returns_default_when_cache_empty(): void
    {
        [$allowance, $timestamp] = $this->identity->loadAllowance($this->request, $this->action);
        self::assertSame(3, $allowance);
        self::assertEqualsWithDelta(time(), $timestamp, 1);
    }

    public function test_save_and_load_allowance(): void
    {
        $this->identity->saveAllowance($this->request, $this->action, 1, 1000000);

        [$allowance, $timestamp] = $this->identity->loadAllowance($this->request, $this->action);
        self::assertSame(1, $allowance);
        self::assertSame(1000000, $timestamp);
    }

    public function test_different_ips_get_independent_allowances(): void
    {
        // Save allowance for first IP
        $this->identity->saveAllowance($this->request, $this->action, 0, 1000000);

        // Create identity with different IP
        $otherIdentity = new IpRateLimitIdentity([
            'limit' => 3,
            'window' => 10,
            'keyPrefix' => 'test-rate-limit',
            'ip' => '10.0.0.1',
        ]);

        // Second IP should still have full allowance (cache miss = default)
        [$allowance, $timestamp] = $otherIdentity->loadAllowance($this->request, $this->action);
        self::assertSame(3, $allowance);
        self::assertEqualsWithDelta(time(), $timestamp, 1);

        // First IP should still be exhausted
        [$allowance] = $this->identity->loadAllowance($this->request, $this->action);
        self::assertSame(0, $allowance);
    }
}
