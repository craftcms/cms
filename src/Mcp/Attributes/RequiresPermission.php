<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Attributes;

use Attribute;

/**
 * @since 6.0.0
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
readonly class RequiresPermission
{
    public function __construct(public string $permission) {}
}
