<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace Craft\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void push(string $heading, string $body, ?string $pluginHandle = null, bool $adminsOnly = false)
 * @method static array get()
 * @method static void markAsRead(array $ids)
 *
 * @see \Craft\Cms\Announcement\Announcements
 */
class Announcements extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Craft\Cms\Announcement\Announcements::class;
    }
}
