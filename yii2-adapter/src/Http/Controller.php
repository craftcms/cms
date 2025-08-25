<?php

namespace CraftCms\Yii2Adapter\Http;

use CraftCms\Cms\Config\GeneralConfig;
use Illuminate\Auth\AuthenticationException;

final class Controller extends \craft\web\Controller
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
        abort_unless(request()->user()->isAdmin(), 403, 'User is not permitted to perform this action.');

        if ($requireAdminChanges && !app(GeneralConfig::class)->allowAdminChanges) {
            abort(403, 'Administrative changes are disallowed in this environment.');
        }
    }
}
