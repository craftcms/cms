<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\test\mockclasses\components;


use craft\base\ComponentInterface;
use Exception;
use yii\base\InvalidConfigException;

/**
 * Class DependencyHeavyComponent.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
class DependencyHeavyComponent implements ComponentInterface
{
    /**
     * DependencyHeavyComponent constructor.
     *
     * @param array $settings
     *
     * @throws InvalidConfigException
     */
    public function __construct(array $settings)
    {
        if (!isset($settings['dependency1'])) {
            throw new Exception('Dependency 1 doesnt exist');
        }
        if (!isset($settings['dependency2'])) {
            throw new Exception('Dependency 2 doesnt exist');
        }
        if (!isset($settings['settingsdependency1'])) {
            throw new Exception('Settings dependency 1 doesnt exist');
        }
    }

    /**
     * @return string
     */
    public static function displayName(): string
    {
        return 'Dependency heavy component';
    }

}