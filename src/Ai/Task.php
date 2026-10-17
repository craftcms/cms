<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai;

use CraftCms\Cms\Ai\Contracts\TaskInterface;
use CraftCms\Cms\Ai\Enums\Modality;

/**
 * Base class for AI tasks.
 *
 * ```php
 * class DescribeImage extends Task
 * {
 *     public static function handle(): string
 *     {
 *         return 'altText:describe';
 *     }
 *
 *     public static function label(): string
 *     {
 *         return t('Describe image', category: 'alt-text');
 *     }
 *
 *     public static function capabilities(): array
 *     {
 *         return [Capability::Vision];
 *     }
 * }
 * ```
 *
 * @since 6.0.0
 */
abstract class Task implements TaskInterface
{
    public static function modality(): Modality
    {
        return Modality::Text;
    }

    public static function capabilities(): array
    {
        return [];
    }

    public static function description(): ?string
    {
        return null;
    }
}
