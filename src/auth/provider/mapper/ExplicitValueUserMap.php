<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\provider\mapper;

use craft\base\Component;
use craft\elements\User;

class ExplicitValueUserMap extends Component implements UserMapInterface
{
    use SetUserValueTrait;

    /**
     * @var mixed
     */
    public mixed $value = null;

    /**
     * @inheritDoc
     */
    public function __invoke(User $user, mixed $data): User
    {
        $this->setValue($user, $this->value);

        return $user;
    }
}
