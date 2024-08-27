<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\sso\mapper;

use craft\elements\User;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @internal
 * @since 5.3.0
 */
interface UserMapInterface
{
    /**
     * @param User $user
     * @param mixed $data
     * @return User
     */
    public function __invoke(User $user, mixed $data): User;
}
