<?php

declare(strict_types=1);

namespace craft\mcp\contentmodel;

/**
 * A content-model audit check that can be registered with {@see CheckRegistry}.
 *
 * @since 5.12.0
 */
interface Check
{
    public static function id(): string;

    /** @return array{count: int, findings: list<mixed>} */
    public function run(int $limit): array;
}
