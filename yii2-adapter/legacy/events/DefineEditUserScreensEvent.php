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
 * Class DefineEditUserScreensEvent
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class DefineEditUserScreensEvent extends Event
{
    /**
     * @var User The currently logged-in user
     */
    public User $currentUser;

    /**
     * @var User The user being edited.
     */
    public User $editedUser;

    /**
     * @var array<string,array> The screens that should be shown for the user being edited.
     *
     * Each screen should be represented by a sub-array whose key is the screen ID, and which has the following keys:
     *
     * - `label` – The screen’s nav item label.
     * - `url` – The screen’s URL (optional; a URL like `myaccount/screen-key`
     *   or `users/x/screen-key` will be used by default).
     */
    public array $screens;
}
