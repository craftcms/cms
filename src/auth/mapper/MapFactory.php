<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\auth\mapper;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Typecast;
use yii\base\InvalidConfigException;

class MapFactory
{
    /**
     * @param string|array|UserMapInterface $config
     * @param string $defaultClass
     * @return UserMapInterface
     * @throws InvalidConfigException
     */
    public static function createUserMap(string|array|UserMapInterface $config, string $defaultClass = UserAttributesMapper::class): UserMapInterface
    {
        if ($config instanceof UserMapInterface) {
            return $config;
        }

        // Normalize the config
        if (is_string($config)) {
            $class = $config;
            $config = [];
        } else {
            // Apply defaults?
            if (is_array($config)) {
                $config = ArrayHelper::merge(
                    [
                        'type' => $defaultClass
                    ],
                    $config
                );
            }

            $class = $config['type'] ?? $config['class'];
            unset($config['type'], $config['__class']);
        }

        // Validate class
        if (!is_subclass_of($class, UserMapInterface::class)) {
            throw new InvalidConfigException("Component class '$class' does not implement UserMapInterface.");
        }

        // Typecast the properties
        Typecast::properties($class, $config);

        // Instantiate and return
        $config['class'] = $class;
        return Craft::createObject($config);
    }
}
