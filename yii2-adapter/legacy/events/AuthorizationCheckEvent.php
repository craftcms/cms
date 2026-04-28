<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\User\Elements\User;

/**
 * Authorization Check Event.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Auth\Events\AuthorizingElement} instead.
 */
class AuthorizationCheckEvent extends Event
{
    /**
     * Constructor
     *
     * @param User $user
     * @param array $config
     */
    public function __construct(User $user, array $config = [])
    {
        $this->user = $user;
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
     * @var User The user to be authorized.
     */
    public User $user;

    /**
     * @var bool|null Whether the user is authorized.
     */
    public ?bool $authorized = false;
}
