<?php
/**
 * Created by PhpStorm.
 * User: Giel Tettelaar PC
 * Date: 9/30/2018
 * Time: 5:18 PM
 */

namespace craftunit\support\mockclasses\components;


use craft\base\ComponentInterface;
use yii\base\InvalidConfigException;

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
            throw new InvalidConfigException('Dependency 1 doesnt exist');
        }
        if (!isset($settings['dependency2'])) {
            throw new InvalidConfigException('Dependency 2 doesnt exist');
        }
        if (!isset($settings['settingsdependency1'])) {
            throw new InvalidConfigException('Settings dependency 1 doesnt exist');
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