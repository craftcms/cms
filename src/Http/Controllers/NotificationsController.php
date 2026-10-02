<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Cp\Notifications\NotificationCenter;
use CraftCms\Cms\Http\RespondsWithFlash;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @since 6.0.0
 */
readonly class NotificationsController
{
    use RespondsWithFlash;

    public function markRead(Request $request, NotificationCenter $notifications): Response
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['uuid'],
        ]);

        $notifications->markAsRead($validated['ids']);

        return $this->asSuccess();
    }
}
