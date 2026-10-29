<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Guidance;

use CraftCms\Cms\Ai\Models\AgentGuidance;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\User\Elements\User;

/**
 * Reads and saves the AI assistant’s guidance: for every site, for one site, and for one user.
 *
 * @since 6.0.0
 */
readonly class Guidance
{
    /**
     * Returns the guidance for every site.
     */
    public function forAllSites(): ?string
    {
        return $this->get(siteId: null, userId: null);
    }

    /**
     * Returns the guidance added for a site.
     */
    public function forSite(int $siteId): ?string
    {
        return $this->get(siteId: $siteId, userId: null);
    }

    /**
     * Returns a user’s own guidance.
     */
    public function forUser(int $userId): ?string
    {
        return $this->get(siteId: null, userId: $userId);
    }

    /**
     * Returns the guidance that applies to a user, as Markdown sections for the AI assistant’s instructions: the
     * guidance for every site, the guidance for the site they’re working in, if any, and their own.
     */
    public function forAssistant(User $user, ?Site $site = null): string
    {
        $name = $user->getFriendlyName() ?? $user->getName();

        $sections = array_filter([
            'For every site' => $this->forAllSites(),
            "For the {$site?->getName()} site" => $site ? $this->forSite($site->id) : null,
            "From {$name}" => $this->forUser($user->id),
        ]);

        return collect($sections)
            ->map(static fn (string $body, string $heading): string => "### {$heading}\n\n{$body}")
            ->implode("\n\n");
    }

    /**
     * Saves guidance for every site (no site or user), for a site, or for a user. Empty guidance is removed.
     *
     * Nothing stops two first saves at the same moment from both adding a row, so the newest row is the one that's read
     * and updated.
     */
    public function save(?int $siteId, ?int $userId, string $body): void
    {
        $body = trim($body);
        $scope = ['siteId' => $siteId, 'userId' => $userId];

        if ($body === '') {
            AgentGuidance::query()->where($scope)->delete();

            return;
        }

        $guidance = AgentGuidance::query()->where($scope)->latest('id')->first() ?? new AgentGuidance()->forceFill($scope);
        $guidance->body = $body;
        $guidance->save();
    }

    private function get(?int $siteId, ?int $userId): ?string
    {
        return AgentGuidance::query()
            ->where(['siteId' => $siteId, 'userId' => $userId])
            ->latest('id')
            ->value('body');
    }
}
