<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

/**
 * RegisterDefaultCardAttributes event is triggered when registering the default card attributes for an element type.
 *
 * @since 6.0.0
 */
final class RegisterDefaultCardAttributes
{
    /**
     * @param  class-string<\CraftCms\Cms\Element\ElementInterface>  $elementType  The element type class
     * @param  array  $cardAttributes  The default card attribute keys
     */
    public function __construct(
        public string $elementType,
        public array $cardAttributes = [],
    ) {}
}
