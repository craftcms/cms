<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Auth;

use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class SessionInfoController
{
    use ConfirmsPasswords;

    public function show(Request $request, GeneralConfig $generalConfig): JsonResponse
    {
        $data = [
            'isGuest' => $request->user() === null,
        ];

        if ($generalConfig->enableCsrfProtection) {
            $data['csrfTokenName'] = Cms::config()->csrfTokenName;
            $data['csrfTokenValue'] = csrf_token();
        }

        if ($user = $request->user()) {
            $data['id'] = $user->id;
            $data['uid'] = $user->uid;
            $data['username'] = $user->username;
            $data['email'] = $user->email;
        }

        return new JsonResponse($data);
    }

    public function confirmTimeout(): JsonResponse
    {
        return new JsonResponse([
            'timeout' => $this->confirmedPasswordTimeout(),
        ]);
    }
}
