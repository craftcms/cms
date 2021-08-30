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
use craft\db\Query;
use craft\db\Table;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use yii\base\Component;
use yii\base\InvalidArgumentException;

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
     * @throws InvalidArgumentException|\yii\base\InvalidConfigException if `$config['type']` does not implement [[BaseCondition]]
     * @throws \yii\base\InvalidConfigException
     * @since 3.5.0
     */
    public function createCondition(array $config): BaseCondition
    {
        $type = ArrayHelper::remove($config, 'type');

        if (!$type || !is_subclass_of($type, BaseCondition::class)) {
            throw new InvalidArgumentException("Invalid condition class: $type");
        }

        $config['class'] = $type;
        /** @var BaseCondition $condition */
        $condition = Craft::createObject($config);

        return $condition;
    }

    /**
     * Creates a condition rule instance from its config.
     *
     * @param array $config
     * @return BaseConditionRule
     * @throws InvalidArgumentException|\yii\base\InvalidConfigException if `$config['type']` does not implement [[BaseConditionRule]]
     * @throws \yii\base\InvalidConfigException
     * @since 3.5.0
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
