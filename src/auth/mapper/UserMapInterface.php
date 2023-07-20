<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\mapper;

use craft\elements\User;

interface UserMapInterface
{
    /**
     * @param User $user
     * @param mixed $data
     * @return void
     */
    public function map(User $user, mixed $data): void;
}
