<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Ai\Guidance\Guidance as AgentGuidance;
use CraftCms\Cms\Mcp\Attributes\RequiresPermission;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\AiGuidance;
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
        private Utilities $utilities,
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

    /**
     * @param  string  $guidance  The whole new guidance, in Markdown. An empty string removes it.
     * @param  int|null  $siteId  The site to save the guidance for. Omit it for the guidance for every site, which needs permission to edit every site.
     * @return array{siteId: int|null, guidance: string|null}
     */
    #[McpTool(
        name: 'guidance.update',
        description: 'Replaces the guidance the site’s team wrote for AI, as the AI Guidance & Skills utility does: for every site, or for the site with siteId. It’s what the AI assistant and MCP clients follow when they work with content, so read it with guidance.get first, and only change what the user asked for. An empty string removes it.',
        annotations: new ToolAnnotations(destructiveHint: true, idempotentHint: true),
    )]
    #[RequiresPermission('utility:ai-guidance')]
    public function update(string $guidance, ?int $siteId = null): array
    {
        if (! $this->utilities->checkAuthorization(AiGuidance::class)) {
            throw new ToolCallException('The user can’t use the AI Guidance & Skills utility.');
        }

        if ($siteId === null && ! AiGuidance::canEditGuidanceForAllSites()) {
            throw new ToolCallException('The user needs permission to edit every site to edit the guidance for all sites. Pass a siteId instead.');
        }

        if ($siteId !== null && Sites::getEditableSiteIds()->doesntContain($siteId)) {
            throw new ToolCallException("Site [$siteId] not found, or the user can’t edit it.");
        }

        $this->guidance->save(siteId: $siteId, userId: null, body: $this->body($guidance));

        return [
            'siteId' => $siteId,
            'guidance' => $siteId !== null ? $this->guidance->forSite($siteId) : $this->guidance->forAllSites(),
        ];
    }

    /**
     * @param  string  $guidance  The whole new guidance, in Markdown. An empty string removes it.
     * @return array{guidance: string|null}
     */
    #[McpTool(
        name: 'guidance.updatePersonal',
        description: 'Replaces the authenticated user’s own guidance for AI, such as the language to reply in, as their account’s AI Assistant screen does. AI follows it along with the site’s guidance, so read it with guidance.get first, and only change what the user asked for. An empty string removes it.',
        annotations: new ToolAnnotations(destructiveHint: true, idempotentHint: true),
    )]
    #[RequiresPermission('accessCp')]
    public function updatePersonal(string $guidance): array
    {
        $userId = $this->actor->user()->getCraftUserId()
            ?? throw new ToolCallException('Personal guidance is only available to saved users.');

        $this->guidance->save(siteId: null, userId: $userId, body: $this->body($guidance));

        return ['guidance' => $this->guidance->forUser($userId)];
    }

    /** Guidance as the utility and the account screen accept it. */
    private function body(string $guidance): string
    {
        if (mb_strlen($guidance) > 65535) {
            throw new ToolCallException('Guidance can’t be longer than 65,535 characters.');
        }

        return $guidance;
    }
}
