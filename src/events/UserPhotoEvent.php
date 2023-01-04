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
 */
class UserPhotoEvent extends UserEvent
{
    /**
     * @var int|null ID of the asset being saved.
     */
    public ?int $photoId = null;
}
