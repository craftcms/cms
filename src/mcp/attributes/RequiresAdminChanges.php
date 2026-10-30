<?php

declare(strict_types=1);

namespace craft\mcp\attributes;

use Attribute;

/**
 * @since 5.12.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
readonly class RequiresAdminChanges
{
}
