<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Gql\Resolvers\ElementMutationResolver;

/**
 * @event AfterPopulateElement The event that is triggered after GraphQL mutation arguments are applied to an element.
 */
class AfterPopulateElement
{
    public function __construct(
        /** @var class-string<ElementMutationResolver> */
        public string $resolverClass,
        /** @var array<string, mixed> */
        public array $arguments,
        public ElementInterface $element,
    ) {}
}
