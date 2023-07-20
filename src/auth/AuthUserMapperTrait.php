<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth;

use craft\auth\mapper\MapFactory;
use craft\auth\mapper\UserMapInterface;
use yii\base\InvalidConfigException;

trait AuthUserMapperTrait
{
    /**
     * @var UserMapInterface|null
     */
    private ?UserMapInterface $_userMapper = null;

    /**
     * @return UserMapInterface|null
     */
    public function getUserMapper(): ?UserMapInterface
    {
        return $this->_userMapper;
    }

    /**
     * @param mixed $mapper
     * @return $this
     * @throws InvalidConfigException
     */
    public function setUserMapper(mixed $mapper): static
    {
        $this->_userMapper = MapFactory::createUserMap($mapper);

        return $this;
    }
}
