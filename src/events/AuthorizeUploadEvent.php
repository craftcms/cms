<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\elements\User;
use yii\base\Event;

/**
 * AuthorizeUploadEvent is used when an upload starts or is completed.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class AuthorizeUploadEvent extends Event
{
    /**
     * @var string The upload handler’s class
     */
    public string $handler;

    /**
     * @var string|null The file’s name, if known
     */
    public ?string $filename = null;

    /**
     * @var int|null The file’s size in bytes, if known
     */
    public ?int $size = null;

    /**
     * @var array The handler’s normalized parameters, such as `folderId` and `context` for asset uploads
     */
    public array $params = [];

    /**
     * @var User|null The uploader, or `null` for a guest
     */
    public ?User $user = null;

    /**
     * @var bool Whether the upload is allowed. Defaults to `true` for users who passed the handler’s checks, and
     * `false` for guests, so guests can only upload when a handler allows them.
     */
    public bool $isValid = false;
}
