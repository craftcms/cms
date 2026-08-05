<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void push(string $heading, string $body, string|null $pluginHandle = null, bool $adminsOnly = false)
 * @method static array get()
 * @method static void markAsRead(int[] $ids)
 *
 * @see \CraftCms\Cms\Announcement\Announcements
 */
class Announcements extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Announcement\Announcements::class;
    }
}
