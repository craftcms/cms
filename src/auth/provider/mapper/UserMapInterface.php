<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\provider\mapper;

use craft\elements\User;

interface UserMapInterface
{
    /**
     * @param User $user
     * @param mixed $data
     * @return User
     */
    public function __invoke(User $user, mixed $data): User;
}
