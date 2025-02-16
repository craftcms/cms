<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;
use craft\base\Event;
use craft\elements\User;

/**
 * Authorization Check Event.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AuthorizationCheckEvent extends Event
{
    /**
     * Constructor
     *
     * @param User $user
     * @param array $config
     */
    public function __construct(public User $user, array $config = [])
    {
        parent::__construct($config);
    }

    /**
     * @var ElementInterface|null The element being authorized.
     *
     * This will only be set if the event was triggered from [[\craft\services\Elements]].
     *
     * @since 4.3.0
     */
    public ?ElementInterface $element = null;

    /**
     * @var bool|null Whether the user is authorized.
     */
    public ?bool $authorized = false;
}
