<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static mixed createCondition(array|string $config)
 * @method static \CraftCms\Cms\Condition\Contracts\ConditionRuleInterface createConditionRule(array|string $config)
 *
 * @see \CraftCms\Cms\Condition\Conditions
 */
class Conditions extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Condition\Conditions::class;
    }
}
