<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\mapper;

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
    public function map(User $user, mixed $data): void
    {
        $this->setValue($user, $this->value);
    }
}
