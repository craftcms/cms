<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

/**
 * UserSavePhotoEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class UserSavePhotoEvent extends UserEvent
{
    /**
     * @var string Filename of the file being saved.
     */
    public string $filename;

    /**
     * @var int|null ID of the asset being saved.
     */
    public ?int $photoId = null;
}
