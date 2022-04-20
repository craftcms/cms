<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\elements\User;

/**
 * Create Element Check Event
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class CreateElementCheckEvent extends AuthorizationCheckEvent
{
    /**
     * Constructor
     *
     * @param User $user
     * @param array $attributes
     * @param array $config
     */
    public function __construct(User $user, array $attributes, array $config = [])
    {
        $this->attributes = $attributes;
        parent::__construct($user, $config);
    }

    /**
     * @var array The attributes the new element would be created with.
     */
    public array $attributes;
}
