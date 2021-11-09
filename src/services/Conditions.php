<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\conditions\ConditionInterface;
use craft\conditions\ConditionRuleInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

/**
 * The Conditions service provides APIs for managing conditions
 *
 * An instance of Conditions service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getConditions()|`Craft::$app->conditions`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Conditions extends Component
{
    /**
     * Creates a condition instance.
     *
     * @param string|array{class: string} $config The condition class or configuration array
     * @return ConditionInterface
     * @throws InvalidArgumentException if the condition does not implement [[ConditionInterface]]
     * @throws InvalidConfigException
     */
    public function createCondition($config): ConditionInterface
    {
        if (is_string($config)) {
            $class = $config;
            $config = [];
        } else {
            $class = ArrayHelper::remove($config, 'class');
        }

        if (!is_subclass_of($class, ConditionInterface::class)) {
            throw new InvalidArgumentException("Invalid condition class: $class");
        }

        /** @var ConditionInterface $condition */
        $condition = Craft::createObject($class);
        $condition->setAttributes($config);
        return $condition;
    }

    /**
     * Creates a condition rule instance.
     *
     * @param string|array{class: string}|array{type: string} $config The condition class or configuration array
     * @return ConditionRuleInterface
     * @throws InvalidArgumentException if the condition rule does not implement [[ConditionRuleInterface]]
     */
    public function createConditionRule($config): ConditionRuleInterface
    {
        if (is_string($config)) {
            $class = $config;
            $config = [];
        } else {
            // Merge `type` in, if this is coming from a condition builder
            if (isset($config['type'])) {
                $type = Json::decodeIfJson(ArrayHelper::remove($config, 'type'));
                if (is_string($type)) {
                    $type = ['class' => $type];
                }
                $config += $type;
            }

            $class = ArrayHelper::remove($config, 'class');
        }

        if (!is_subclass_of($class, ConditionRuleInterface::class)) {
            throw new InvalidArgumentException("Invalid condition rule class: $class");
        }

        /** @var ConditionRuleInterface $rule */
        $rule = Craft::createObject($class);
        $rule->setAttributes($config);
        return $rule;
    }
}
