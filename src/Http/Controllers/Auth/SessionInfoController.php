<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Auth;

use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

readonly class SessionInfoController
{
    use ConfirmsPasswords;

    public function show(Request $request): JsonResponse
    {
        $data = [
            'isGuest' => $request->user() === null,
            'csrfTokenName' => '_token',
            'csrfTokenValue' => csrf_token(),
        ];

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
