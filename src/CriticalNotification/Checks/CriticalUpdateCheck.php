<?php

declare(strict_types=1);

namespace CraftCms\Cms\CriticalNotification\Checks;

use CraftCms\Cms\CriticalNotification\CriticalCheck;
use CraftCms\Cms\CriticalNotification\CriticalNotification;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Update\Data\Update;
use CraftCms\Cms\Update\Data\UpdateRelease;
use CraftCms\Cms\Update\Updates;
use RuntimeException;

use function CraftCms\Cms\t;

/**
 * Sends a notification when Craft’s updates API marks a Craft or plugin release as critical.
 *
 * @since 6.0.0
 */
class CriticalUpdateCheck implements CriticalCheck
{
    public function __construct(
        private readonly Updates $updates,
        private readonly Plugins $plugins,
    ) {}

    public function key(): string
    {
        return 'critical-update';
    }

    public function run(): ?CriticalNotification
    {
        $updates = $this->updates->getUpdates(refresh: true);

        // Updates::getUpdates() returns empty data when the request fails
        if ($updates->dateFetched === null) {
            throw new RuntimeException('Couldn’t check for critical updates.');
        }

        $packages = [];

        foreach (['craft' => $updates->cms, ...$updates->plugins] as $handle => $update) {
            $versions = array_map(
                fn (UpdateRelease $release): string => $release->version,
                array_filter($update->releases, fn (UpdateRelease $release): bool => $release->isCritical($update)),
            );

            if ($versions !== []) {
                $packages[] = sprintf('%s %s', $this->name($handle, $update), implode(', ', $versions));
            }
        }

        if ($packages === []) {
            return null;
        }

        return new CriticalNotification(
            key: $this->key(),
            title: t('Critical updates are available'),
            message: t('Install these updates as soon as possible: {packages}.', [
                'packages' => implode(', ', $packages),
            ]),
            url: Url::cpUrl('utilities/updates'),
        );
    }

    private function name(string $handle, Update $update): string
    {
        if ($handle === 'craft') {
            return 'Craft CMS';
        }

        return $this->plugins->getPlugin($handle)->name ?? $update->packageName ?: $handle;
    }
}
