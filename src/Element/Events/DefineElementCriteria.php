<?php

namespace CraftCms\Cms\Element\Events;

/** @since 6.0.0 */
final class DefineElementCriteria
{
    public function __construct(
        /** @var array The criteria that should be used to query for elements. */
        public array $criteria = [],
    ) {}
}
