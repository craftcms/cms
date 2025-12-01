<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\sso\mapper;

use craft\base\Component;
use craft\elements\User;

/**
 * Set an explicit value as a User's attribute
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @internal
 * @since 5.3.0
 */
class ExplicitUserMapper extends Component implements UserMapInterface
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
