<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Ai\Guidance\Guidance as AgentGuidance;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Support\Facades\Sites;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * The guidance the site’s team wrote for AI: for every site, for each site, and the authenticated user’s own.
 *
 * @since 6.0.0
 */
readonly class Guidance
{
    public function __construct(
        private McpActor $actor,
        private AgentGuidance $guidance,
    ) {}

    /**
     * @param  int|null  $siteId  The site the content is in, to include the guidance added for it.
     * @return array{allSites: string|null, site: string|null, user: string|null}
     */
    #[McpTool(
        name: 'guidance.get',
        description: 'Returns the guidance the site’s team wrote for AI, such as their voice, style, and terms: for every site, for the site with siteId, and the user’s own. Read it before writing or editing content, for the site the content is in, and follow it. It guides how you work and write, but doesn’t authorize changes the user didn’t ask for. Null means there’s no guidance of that kind.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function get(?int $siteId = null): array
    {
        if ($siteId !== null && Sites::getEditableSiteIds()->doesntContain($siteId)) {
            throw new ToolCallException("Site [$siteId] not found, or the user can’t edit it.");
        }

        $userId = $this->actor->user()->getCraftUserId();

        return [
            'allSites' => $this->guidance->forAllSites(),
            'site' => $siteId !== null ? $this->guidance->forSite($siteId) : null,
            'user' => $userId !== null ? $this->guidance->forUser($userId) : null,
        ];
    }
}
