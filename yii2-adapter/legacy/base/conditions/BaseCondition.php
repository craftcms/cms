<?php

namespace craft\base\conditions;

use craft\events\RegisterConditionRulesEvent;
use craft\helpers\Html;
use CraftCms\Cms\Condition\Events\RegisterConditionRules;
use CraftCms\Cms\Support\Arr;
use CraftCms\Yii2Adapter\ModelWrapper;
use Illuminate\Support\Facades\Event;
use yii\validators\Validator;

/**
 * BaseCondition provides a base implementation for conditions.
 *
 * @property ConditionRuleInterface[] $conditionRules The rules this condition is configured with
 * @property-read array $config The condition’s portable config
 * @property-read string $builderHtml The HTML for the condition builder, including its outer container element
 * @property-read string $builderInnerHtml The inner HTML for the condition builder, excluding its outer container element
 * @property-read string[]|array{class: string}[] $conditionRuleTypes The available rule types for this condition
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Condition\BaseCondition} instead.
 */
abstract class BaseCondition extends \CraftCms\Cms\Condition\BaseCondition
{
    /**
     * @event RegisterConditionRulesEvent The event that is triggered when defining the selectable condition rules.
     * @see getSelectableConditionRules()
     */
    public const EVENT_REGISTER_CONDITION_RULES = 'registerConditionRules';

    public function getRules(): array
    {
        $yiiRules = $this->defineRules();

        // Ensure it's set and an array
        $rules = parent::getRules();
        $rules['*'] ??= [];
        $rules['*'] = Arr::wrap($rules['*']);

        array_unshift($rules['*'], function($attribute, $value, $fail) use ($yiiRules) {
            foreach ($yiiRules as $rule) {
                $attributes = (array) $rule[0];
                $type = $rule[1];
                $options = array_slice($rule, 2);

                if (!in_array($attribute, $attributes, true)) {
                    continue;
                }

                $validator = Validator::createValidator($type, new ModelWrapper($this), $attributes, $options);
                $validator->validateAttribute(new ModelWrapper($this), $attribute);
            }
        });

        return $rules;
    }

    public function defineRules(): array
    {
        return [];
    }

    public static function registerEvents(): void
    {
        Event::listen(function(RegisterConditionRules $event) {
            // Fire a 'registerConditionRules' event
            if (\craft\base\Event::hasHandlers(static::class, self::EVENT_REGISTER_CONDITION_RULES)) {
                $yiiEvent = new RegisterConditionRulesEvent([
                    'conditionRules' => $event->conditionRules,
                ]);
                \craft\base\Event::trigger(static::class, self::EVENT_REGISTER_CONDITION_RULES, $yiiEvent);
                $event->conditionRules = $yiiEvent->conditionRules;
            }
        });
    }
}
