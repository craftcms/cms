<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

/**
 * @see \CraftCms\Cms\Field\Link::types()
 */
final class RegisterLinkTypes
{
    public function __construct(
        /** @var string[] List of link types. */
        public array $types = [],
    ) {}
}
