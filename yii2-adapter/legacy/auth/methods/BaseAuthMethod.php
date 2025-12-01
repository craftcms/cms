<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\methods;

use craft\base\Component;
use craft\elements\User;

/**
 * BaseAuthMethod provides a base implementation of an authentication method.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
abstract class BaseAuthMethod extends Component implements AuthMethodInterface
{
    /**
     * @var User The current user
     */
    protected User $user;

    /**
     * @inheritdoc
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @inheritdoc
     */
    public function getActionMenuItems(): array
    {
        return [];
    }
}
