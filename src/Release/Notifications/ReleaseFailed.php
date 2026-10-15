<?php

declare(strict_types=1);

namespace CraftCms\Cms\Release\Notifications;

use CraftCms\Cms\Cp\Notifications\CpNotification;
use CraftCms\Cms\Release\Models\Release;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class ReleaseFailed extends CpNotification
{
    public function __construct(Release $release, string $error)
    {
        parent::__construct(t('The release “{name}” could not be published. {error}', [
            'name' => $release->name,
            'error' => $error,
        ]));

        $this
            ->kind('release-failed')
            ->title(t('Release publication failed'))
            ->url(route('craft.cp.releases.show', $release));
    }
}
