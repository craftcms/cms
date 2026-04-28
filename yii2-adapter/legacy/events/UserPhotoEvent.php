<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

/**
 * UserPhotoEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\User\Events\SavingUserPhoto}, {@see \CraftCms\Cms\User\Events\UserPhotoSaved}, {@see \CraftCms\Cms\User\Events\DeletingUserPhoto}, or {@see \CraftCms\Cms\User\Events\UserPhotoDeleted} instead.
 */
class UserPhotoEvent extends UserEvent
{
    /**
     * @var int|null ID of the asset being saved.
     */
    public ?int $photoId = null;
}
