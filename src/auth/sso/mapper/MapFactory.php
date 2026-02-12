<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\sso\mapper;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Typecast;
use yii\base\InvalidConfigException;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @internal
 * @since 5.3.0
 */
class MapFactory
{
    /**
     * @param string|array|callable|UserMapInterface $mapper
     * @param string $defaultClass
     * @return UserMapInterface
     * @throws InvalidConfigException
     */
    public static function createUserMap(string|array|callable|UserMapInterface $mapper, string $defaultClass = UserAttributesMapper::class): UserMapInterface
    {
        /** When $mapper is callable or UserMapInterface */
        if (is_callable($mapper)) {
            return $mapper;
        }

        // Normalize the config
        if (is_string($mapper)) {
            $class = $mapper;
            $mapper = [];
        } else {
            // Apply defaults?
            $mapper = ArrayHelper::merge(
                [
                    'class' => $defaultClass,
                ],
                $mapper
            );

            $class = $mapper['class'];
            unset($mapper['type'], $mapper['__class']);
        }

        // Validate class
        if (!is_subclass_of($class, UserMapInterface::class)) {
            throw new InvalidConfigException("Component class '$class' does not implement UserMapInterface.");
        }

        // Typecast the properties
        Typecast::properties($class, $mapper);

        // Instantiate and return
        $mapper['class'] = $class;
        return Craft::createObject($mapper);
    }
}
