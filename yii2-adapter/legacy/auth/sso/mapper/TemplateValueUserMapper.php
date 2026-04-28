<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\sso\mapper;

use craft\base\Component;
use CraftCms\Cms\User\Elements\User;
use function CraftCms\Cms\renderObjectTemplate;

/**
 * Set a value from a parsed view template as a User's attribute
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @internal
 * @since 5.3.0
 * @deprecated 6.0.0 use the Laravel Socialite {@see \CraftCms\Cms\Auth\OAuth\OAuth} implementation instead.
 */
class TemplateValueUserMapper extends Component implements UserMapInterface
{
    use SetUserValueTrait;

    /**
     * @var string
     */
    public string $template;

    /**
     * @inheritDoc
     */
    public function __invoke(User $user, mixed $data): User
    {
        $value = renderObjectTemplate(
            $this->template,
            [
                'property' => $this->craftProperty,
                'user' => $user,
                'data' => $data,
            ]
        );

        $this->setValue($user, $value);

        return $user;
    }
}
