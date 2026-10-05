<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\User\Data\Permission;
use CraftCms\Cms\User\Data\PermissionGroup;
use CraftCms\Cms\User\UserPermissions;
use Illuminate\Support\ServiceProvider;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class McpServiceProvider extends ServiceProvider
{
    public function boot(UserPermissions $userPermissions): void
    {
        $userPermissions->registerPermissionGroup('mcp', static fn (): PermissionGroup => new PermissionGroup(
            handle: 'mcp',
            heading: t('MCP'),
            permissions: collect([
                new Permission('useCraftMcp', t('Use Craft MCP')),
            ]),
        ));
    }
}
