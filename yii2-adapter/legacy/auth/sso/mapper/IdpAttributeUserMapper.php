<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\sso\mapper;

use craft\base\Component;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Log;

/**
 * Set a value from the IdP as a User's attribute
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @internal
 * @since 5.3.0
 * @deprecated 6.0.0 use the Laravel Socialite {@see \CraftCms\Cms\Auth\OAuth\OAuth} implementation instead.
 */
class IdpAttributeUserMapper extends Component implements UserMapInterface
{
    use SetUserValueTrait;

    /**
     * @var string
     */
    public string $idpProperty;

    /**
     * @inheritDoc
     */
    public function __invoke(User $user, mixed $data): User
    {
        $value = Arr::get($data, $this->idpProperty);

        if (is_null($value)) {
            Log::warning(
                sprintf(
                    "Attribute mapper value was not found in IdP data set: %s. Skipping",
                    $this->idpProperty
                ),
                ["auth"]
            );

            return $user;
        }

        $this->setValue($user, $value);

        return $user;
    }
}
