<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\Sites;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\ToolAnnotations;

/**
 * @since 6.0.0
 */
class Craft
{
    /** @return array{version: string, edition: string, name: string, isInstalled: bool, siteCount: ?int} */
    #[McpTool(
        name: 'info.get',
        description: 'Returns basic information about the running Craft CMS application.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function info(): array
    {
        $isInstalled = Cms::isInstalled();

        return [
            'version' => Cms::version(),
            'edition' => Edition::get()->handle(),
            'name' => Cms::systemName(),
            'isInstalled' => $isInstalled,
            'siteCount' => $isInstalled ? Sites::getTotalSites() : null,
        ];
    }
}
