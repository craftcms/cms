<?php

namespace CraftCms\Cms\Field\Events;

/**
 * @see \CraftCms\Cms\Field\Link::types()
 * @since 6.0.0
 */
final class RegisterLinkTypes
{
    public function __construct(
        /** @var string[] List of link types. */
        public array $types = [],
    ) {}
}
