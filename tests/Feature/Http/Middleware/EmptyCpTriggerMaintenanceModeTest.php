<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Feature\Http\Middleware;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Tests\TestCase;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Override;

class EmptyCpTriggerMaintenanceModeTest extends TestCase
{
    /** @param Application $app */
    #[Override]
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app->make(ConfigRepository::class)->set('craft.general.cpTrigger', '');
    }

    public function test_public_shared_actions_are_blocked_during_maintenance_mode(): void
    {
        $this->assertNull(Cms::config()->cpTrigger);

        auth()->logout();
        app()->maintenanceMode()->activate([]);

        $this->get('/actions/graphql/api?query=%7B__typename%7D')
            ->assertServiceUnavailable();
    }
}
