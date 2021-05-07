<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\authentication\base\TypeInterface;
use craft\models\AuthenticationState;
use yii\base\InvalidConfigException;

/**
 * Class Authentication
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Authentication
{
    /**
     * Create an authentication type based on a config.
     *
     * @param array $typeConfig
     * @param AuthenticationState $state
     * @return TypeInterface
     * @throws InvalidConfigException
     */
    public static function createStepFromConfig(array $typeConfig, AuthenticationState $state): TypeInterface
    {
        $class = $typeConfig['type'];

        if (!is_subclass_of($class, TypeInterface::class)) {
            throw new InvalidConfigException('Impossible to create authentication type.');
        }

        $settings = array_merge($typeConfig['settings'] ?? [], ['state' => $state]);

        /** @var TypeInterface $type */
        $type = Craft::createObject($class, [$settings]);
        return $type;
    }
}
