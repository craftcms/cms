<?php

declare(strict_types=1);

namespace craft\services;

use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

/**
 * The Conditions service provides APIs for managing conditions.
 *
 * An instance of the Conditions service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getConditions()|`Craft::$app->getConditions()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Condition\Conditions} instead.
 */
class Conditions extends Component
{
    /**
     * Creates a condition instance.
     *
     * @template T of ConditionInterface
     * @param array|class-string<T> $config The condition class or configuration array
     *
     * @phpstan-param array{class:class-string<T>}|class-string<T> $config
     * @return ConditionInterface
     * @throws InvalidArgumentException if the condition does not implement [[ConditionInterface]]
     * @throws InvalidConfigException
     */
    public function createCondition(array|string $config): ConditionInterface
    {
        return app(\CraftCms\Cms\Condition\Conditions::class)->createCondition($config);
    }

    /**
     * Creates a condition rule instance.
     *
     * @param array|string $config The condition class or configuration array
     * @phpstan-param array{class: string}|array{type:string}|string $config The condition class or configuration array
     * @return ConditionRuleInterface
     * @throws InvalidArgumentException if the condition rule does not implement [[ConditionRuleInterface]]
     */
    public function createConditionRule(array|string $config): ConditionRuleInterface
    {
        return app(\CraftCms\Cms\Condition\Conditions::class)->createConditionRule($config);
    }
}
