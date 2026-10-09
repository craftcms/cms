<?php

declare(strict_types=1);

namespace craft\gql\base;

abstract class ElementMutationResolver extends \CraftCms\Cms\Gql\Resolvers\ElementMutationResolver
{
    public const EVENT_BEFORE_POPULATE_ELEMENT = 'beforeMutationPopulateElement';
    public const EVENT_AFTER_POPULATE_ELEMENT = 'afterMutationPopulateElement';
}
