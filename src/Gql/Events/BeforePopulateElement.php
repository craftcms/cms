<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Gql\Resolvers\ElementMutationResolver;

/**
 * @event BeforePopulateElement The event that is triggered before GraphQL mutation arguments are applied to an element.
 */
class BeforePopulateElement
{
    public function __construct(
        /** @var class-string<ElementMutationResolver> */
        public string $resolverClass,
        /** @var array<string, mixed> */
        public array $arguments,
        public ElementInterface $element,
    ) {}
}
