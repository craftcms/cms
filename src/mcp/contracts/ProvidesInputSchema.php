<?php

declare(strict_types=1);

namespace craft\mcp\contracts;

/**
 * @since 5.12.0
 */
interface ProvidesInputSchema
{
    /** @return array<string, mixed> */
    public function getMcpInputSchema(): array;
}
