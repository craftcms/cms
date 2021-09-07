<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\conditions\BaseCondition;
use craft\conditions\BaseConditionRule;
use craft\helpers\ArrayHelper;
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
     * Creates a condition instance from its config.
     *
     * @param array $config
     * @return BaseCondition
     * @throws InvalidArgumentException|InvalidConfigException if `$config['type']` does not implement [[BaseCondition]]
     * @throws InvalidConfigException
     */
    public function createCondition(array $config): BaseCondition
    {
        $type = ArrayHelper::remove($config, 'type');

        if (!$type || !is_subclass_of($type, BaseCondition::class)) {
            throw new InvalidArgumentException("Invalid condition class: $type");
        }

        $config['class'] = $type;
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::createObject($config);
    }

    /**
     * Creates a condition rule instance from its config.
     *
     * @param array $config
     * @return BaseConditionRule
     * @throws InvalidArgumentException|InvalidConfigException if `$config['type']` does not implement [[BaseConditionRule]]
     */
    public function createConditionRule(array $config): BaseConditionRule
    {
        $type = ArrayHelper::remove($config, 'type');

        if (!$type || !is_subclass_of($type, BaseConditionRule::class)) {
            throw new InvalidArgumentException("Invalid condition rule class: $type");
        }

        $config['class'] = $type;
        /** @var BaseConditionRule $conditionRule */
        $conditionRule = Craft::createObject($type);
        $conditionRule->setAttributes($config);

        return $conditionRule;
    }
}
