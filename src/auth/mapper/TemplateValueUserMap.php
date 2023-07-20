<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\mapper;

use Craft;
use craft\base\Component;
use craft\elements\User;

class TemplateValueUserMap extends Component implements UserMapInterface
{
    use SetUserValueTrait;

    /**
     * @var mixed
     */
    public string $template;

    /**
     * @inheritDoc
     */
    public function map(User $user, mixed $data): void
    {
        $value = Craft::$app->view->renderObjectTemplate(
            $this->template,
            [
                'property' => $this->craftProperty,
                'user' => $user,
                'data' => $data
            ]
        );

        $this->setValue($user, $value);
    }
}
