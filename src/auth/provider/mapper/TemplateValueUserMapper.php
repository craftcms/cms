<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\provider\mapper;

use Craft;
use craft\base\Component;
use craft\elements\User;

/**
 * Set a value from a parsed view template as a User's attribute
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
        $value = Craft::$app->view->renderObjectTemplate(
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
