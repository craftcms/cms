<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use craft\elements\User;

/**
 * AuthorizeUploadEvent is used when an asset upload is authorized.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\AssetUploads::allowGuestUploadsUsing()} instead.
 */
class AuthorizeUploadEvent extends Event
{
    /**
     * @var string The upload handler’s class, {@see \CraftCms\Cms\Asset\AssetUploads}
     */
    public string $handler;

    /**
     * @var string|null The file’s name
     */
    public ?string $filename = null;

    /**
     * @var int|null The file’s size in bytes
     */
    public ?int $size = null;

    /**
     * @var array The handler’s normalized parameters, such as `folderId` and `context`
     */
    public array $params = [];

    /**
     * @var User|null The uploader, or `null` for a guest
     */
    public ?User $user = null;

    /**
     * @var bool Whether the upload is allowed. Defaults to `true` for users who passed the handler’s checks, and for
     * guests that a {@see \CraftCms\Cms\Asset\AssetUploads::allowGuestUploadsUsing()} callback allowed.
     */
    public bool $isValid = false;
}
