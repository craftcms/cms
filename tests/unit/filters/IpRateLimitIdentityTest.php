<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\filters;

use Craft;
use craft\filters\IpRateLimitIdentity;
use craft\test\TestCase;
use yii\base\Action;
use yii\web\Controller;

/**
 * Unit tests for IpRateLimitIdentity
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.x
 */
class IpRateLimitIdentityTest extends TestCase
{
    private IpRateLimitIdentity $identity;
    private Action $action;

    protected function setUp(): void
    {
        parent::setUp();

        Craft::$app->getCache()->flush();

        $this->identity = new IpRateLimitIdentity([
            'limit' => 3,
            'window' => 10,
            'keyPrefix' => 'test-rate-limit',
            'ip' => '192.168.1.1',
        ]);

        $controller = $this->createMock(Controller::class);
        $this->action = new Action('test-action', $controller);
    }

    /**
     * @return void
     */
    public function testGetRateLimit(): void
    {
        [$limit, $window] = $this->identity->getRateLimit(null, $this->action);
        self::assertSame(3, $limit);
        self::assertSame(10, $window);
    }

    /**
     * @return void
     */
    public function testLoadAllowanceReturnsDefaultWhenCacheEmpty(): void
    {
        [$allowance, $timestamp] = $this->identity->loadAllowance(null, $this->action);
        self::assertSame(3, $allowance);
        self::assertEqualsWithDelta(time(), $timestamp, 1);
    }

    /**
     * @return void
     */
    public function testSaveAndLoadAllowance(): void
    {
        $this->identity->saveAllowance(null, $this->action, 1, 1000000);

        [$allowance, $timestamp] = $this->identity->loadAllowance(null, $this->action);
        self::assertSame(1, $allowance);
        self::assertSame(1000000, $timestamp);
    }

    /**
     * @return void
     */
    public function testDifferentIpsGetIndependentAllowances(): void
    {
        // Save allowance for first IP
        $this->identity->saveAllowance(null, $this->action, 0, 1000000);

        // Create identity with different IP
        $otherIdentity = new IpRateLimitIdentity([
            'limit' => 3,
            'window' => 10,
            'keyPrefix' => 'test-rate-limit',
            'ip' => '10.0.0.1',
        ]);

        // Second IP should still have full allowance (cache miss = default)
        [$allowance, $timestamp] = $otherIdentity->loadAllowance(null, $this->action);
        self::assertSame(3, $allowance);
        self::assertEqualsWithDelta(time(), $timestamp, 1);

        // First IP should still be exhausted
        [$allowance] = $this->identity->loadAllowance(null, $this->action);
        self::assertSame(0, $allowance);
    }
}
