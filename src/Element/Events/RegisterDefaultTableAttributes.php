<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

/**
 * RegisterDefaultTableAttributes event is triggered when registering the default table attributes for an element type.
 *
 * @since 6.0.0
 */
final class RegisterDefaultTableAttributes
{
    /**
     * @param  class-string<\CraftCms\Cms\Element\ElementInterface>  $elementType  The element type class
     * @param  string  $source  The source key
     * @param  array  $tableAttributes  The default table attribute keys
     */
    public function __construct(
        public string $elementType,
        public string $source,
        public array $tableAttributes = [],
    ) {}
}
