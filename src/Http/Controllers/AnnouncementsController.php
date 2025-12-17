<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Announcement\Announcements;
use CraftCms\Cms\Http\RespondsWithFlash;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class AnnouncementsController
{
    use RespondsWithFlash;

    public function markRead(Request $request, Announcements $announcements): Response
    {
        $announcements->markAsRead($request->array('ids'));

        return $this->asSuccess();
    }
}
