<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Attributes;

use Attribute;

/**
 * @since 6.0.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
readonly class RequiresAdminChanges {}
