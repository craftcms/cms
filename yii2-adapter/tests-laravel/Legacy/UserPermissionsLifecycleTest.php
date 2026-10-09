<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\Legacy;

use Craft;
use CraftCms\Cms\User\UserPermissions as CoreUserPermissions;
use CraftCms\Yii2Adapter\Tests\TestCase;
use Mockery;

class UserPermissionsLifecycleTest extends TestCase
{
    public function testLegacyServiceResolvesTheCurrentScopedCoreService(): void
    {
        $services = [];

        app()->scoped(CoreUserPermissions::class, function() use (&$services) {
            $service = Mockery::mock(CoreUserPermissions::class);
            $service->shouldReceive('reset')->once();
            $services[] = $service;

            return $service;
        });

        $legacyService = Craft::$app->getUserPermissions();
        $legacyService->reset();

        app()->forgetScopedInstances();

        $legacyService->reset();

        self::assertCount(2, $services);
        self::assertNotSame($services[0], $services[1]);
    }
}
