<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static array validate(\CraftCms\Cms\Condition\Contracts\ConditionInterface $condition)
 * @method static \CraftCms\Cms\Condition\Contracts\ConditionRuleInterface createConditionRule(array|string $config)
 *
 * @see \CraftCms\Cms\Condition\Conditions
 */
class Conditions extends Facade
{
    /**
     * @template T of \CraftCms\Cms\Condition\Contracts\ConditionInterface = \CraftCms\Cms\Condition\Contracts\ConditionInterface
     *
     * @param  class-string<T>|array{class: class-string<T>}|array<string, mixed>  $config
     * @return T
     */
    public static function createCondition(array|string $config): ConditionInterface
    {
        return static::getFacadeRoot()->createCondition($config);
    }

    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Condition\Conditions::class;
    }
}
