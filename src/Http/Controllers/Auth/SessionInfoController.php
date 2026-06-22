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
            'isGuest' => $request->craftUser() === null,
            'csrfTokenName' => '_token',
            'csrfTokenValue' => csrf_token(),
        ];

        if ($user = $request->craftUser()) {
            $userElement = $user->asElement();
            $data['id'] = $user->getCraftUserId();
            $data['uid'] = $userElement->uid;
            $data['username'] = $userElement->username;
            $data['email'] = $userElement->email;
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
