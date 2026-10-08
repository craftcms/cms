<?php

declare(strict_types=1);

namespace CraftCms\Cms\Release\Notifications;

use CraftCms\Cms\Cp\Notifications\CpNotification;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class ReleaseFailed extends CpNotification
{
    public function __construct(string $releaseName, string $error)
    {
        parent::__construct(t('The release “{name}” could not be published. {error}', [
            'name' => $releaseName,
            'error' => $error,
        ]));

        $this
            ->kind('release-failed')
            ->title(t('Release publication failed'));
    }
}
