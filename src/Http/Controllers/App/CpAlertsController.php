<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\App;

use CraftCms\Cms\Auth\Concerns\EnforcesPermissions;
use CraftCms\Cms\Cp\Alerts;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\User\Users;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CpAlertsController
{
    use EnforcesPermissions;
    use RespondsWithFlash;

    public function index(Request $request, Alerts $alerts): JsonResponse
    {
        $this->requirePermission('accessCp');

        $path = $request->validate([
            'path' => ['required', 'string'],
        ])['path'];

        return new JsonResponse([
            'alerts' => $alerts->get($path, true),
        ]);
    }

    public function destroy(Request $request, Users $users): Response
    {
        $this->requirePermission('accessCp');

        $message = $request->validate([
            'message' => ['required', 'string'],
        ])['message'];

        $users->shunMessageForUser($request->user()->id, $message, now()->addDay()->toDateTime());

        return $this->asSuccess();
    }
}
