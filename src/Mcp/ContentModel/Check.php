<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\ContentModel;

/**
 * A content-model audit check that can be registered with {@see CheckRegistry}.
 *
 * @since 6.0.0
 */
interface Check
{
    public static function id(): string;

    /** @return array{count: int, findings: list<mixed>} */
    public function run(int $limit): array;
}
