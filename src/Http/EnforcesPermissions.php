<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http;

use Craft;
use craft\elements\Entry;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Support\Facades\Auth;

use function CraftCms\Cms\t;

trait EnforcesPermissions
{
    protected function enforeSitePermission(Site $site): void
    {
        if (! Sites::isMultiSite()) {
            return;
        }

        $this->requirePermission('editSite:'.$site->uid);
    }

    protected function enforceEditEntryPermissions(Entry $entry, bool $duplicate = false): void
    {
        if ($duplicate) {
            $id = $entry->id;
            $entry->id = null;
        }

        $canSave = Craft::$app->getElements()->canSave($entry);

        if ($duplicate) {
            $entry->id = $id;
        }

        abort_unless($canSave, 403, 'User is not authorized to perform this action.');
    }

    protected function requirePermission(string $permission): void
    {
        if (! $user = Auth::user()) {
            abort(403, 'User is not authenticated.');
        }

        if (! $user->can($permission)) {
            abort(403, 'User is not permitted to perform this action.');
        }
    }

    protected function requireElevatedSession(): void
    {
        Craft::$app->getUser()->setIdentity(
            Craft::$app->getUsers()->getUserById(Auth::user()->id),
        );

        abort_unless(
            Craft::$app->getUser()->getHasElevatedSession(),
            403,
            t('This action may only be performed with an elevated session.'),
        );
    }
}
