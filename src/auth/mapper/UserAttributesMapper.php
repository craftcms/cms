<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\mapper;

use craft\elements\User;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;

class UserAttributesMapper extends BaseObject implements UserMapInterface
{
    /**
     * @var UserMapInterface[]
     */
    public array $_attributes = [];

    /**
     * @var string
     */
    public string $defaultAttributeClass = IdpAttributeUserMap::class;

    /**
     * @param array $attributes
     * @return $this
     * @throws InvalidConfigException
     */
    public function setAttributes(array $attributes): static
    {
        foreach ($attributes as $attribute) {
            $this->_attributes[] = MapFactory::createUserMap($attribute, $this->defaultAttributeClass);
        }

        return $this;
    }

    /**
     * @param User $user
     * @param mixed $data
     * @return void
     */
    public function map(User $user, mixed $data): void
    {
        foreach ($this->_attributes as $map) {
            $map->map($user, $data);
        }
    }
}
