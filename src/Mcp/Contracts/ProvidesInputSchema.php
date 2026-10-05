<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Contracts;

/**
 * @since 6.0.0
 */
interface ProvidesInputSchema
{
    /** @return array<string, mixed> */
    public function getMcpInputSchema(): array;
}
