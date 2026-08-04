<?php

namespace CraftCms\Yii2Adapter\Http;

use CraftCms\Cms\Cms;
use Illuminate\Auth\AuthenticationException;

use function CraftCms\Cms\currentUser;

class Controller extends \craft\web\Controller
{
    public function requireLogin(): void
    {
        if (request()->user()) {
            return;
        }

        throw new AuthenticationException();
    }

    public function requireAdmin(bool $requireAdminChanges = true): void
    {
        abort_unless(currentUser()?->isAdmin(), 403, 'User is not permitted to perform this action.');

        if ($requireAdminChanges && !Cms::config()->allowAdminChanges) {
            abort(403, 'Administrative changes are disallowed in this environment.');
        }
    }
}
