<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Concerns;

use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

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
            $entry->id = $id;
        }

        Gate::authorize('save', $entry);
    }

    protected function requireSessionAuthorization(string $permission): void
    {
        if (! SessionAuth::checkAuthorization($permission)) {
            abort(403, 'User is not authorized to perform this action.');
        }
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

    protected function requireAdmin(): void
    {
        abort_unless(Auth::user()->isAdmin(), 403, 'User is not permitted to perform this action.');
    }
}
