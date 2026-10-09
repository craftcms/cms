<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\sso\mapper;

use CraftCms\Cms\User\Elements\User;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @internal
 * @since 5.3.0
 * @deprecated 6.0.0 use the Laravel Socialite {@see \CraftCms\Cms\Auth\OAuth\OAuth} implementation instead.
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
