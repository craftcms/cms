<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\License\License;
use CraftCms\Cms\Update\Updates;

use function CraftCms\Cms\currentUser;

/**
 * What the “About Craft CMS” dashboard widget and command palette view show: Craft’s version and edition, any
 * trials of Craft or plugins that are installed, and, for users who can update Craft, whether updates are available.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class AboutCraft
{
    public function __construct(
        private License $license,
        private Updates $updates,
    ) {}

    /**
     * @return array{
     *     version: string,
     *     edition: string,
     *     trial: array{message: string, cartUrl: string}|null,
     *     updates: array{cached: bool, total: int, dateFetched: string|null}|null,
     * }
     */
    public function toArray(): array
    {
        return [
            'version' => Cms::VERSION,
            'edition' => Edition::get()->name,
            'trial' => $this->license->trialInfo(),
            'updates' => currentUser()?->can('performUpdates') ? $this->updates() : null,
        ];
    }

    /**
     * Whether the “About Craft CMS” view has something to act on: updates for a user who can update Craft, or
     * trials to buy. Only what’s cached is considered, so this never checks for updates or fetches license info.
     */
    public function needsAttention(): bool
    {
        if ($this->license->trialInfo() !== null) {
            return true;
        }

        return currentUser()?->can('performUpdates') && $this->updates->totalAvailableUpdates() > 0;
    }

    /**
     * Whether updates are available, as far as Craft knows. If they haven’t been fetched lately, the UI checks.
     *
     * @return array{cached: bool, total: int, dateFetched: string|null}
     */
    private function updates(): array
    {
        $cached = $this->updates->isUpdateInfoCached();

        return [
            'cached' => $cached,
            'total' => $this->updates->totalAvailableUpdates(),
            'dateFetched' => $cached
                ? ($this->updates->getUpdates()->dateFetched ?? null)?->format(DATE_ATOM)
                : null,
        ];
    }
}
