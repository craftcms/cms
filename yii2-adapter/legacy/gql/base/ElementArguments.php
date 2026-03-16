<?php

declare(strict_types=1);

namespace craft\gql\base;

abstract class ElementArguments extends \CraftCms\Cms\Gql\Arguments\ElementArguments
{
    public const EVENT_DEFINE_ARGUMENTS = 'defineArguments';
}
