<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai;

use CraftCms\Cms\Ai\Contracts\TaskInterface;
use CraftCms\Cms\Ai\Enums\Modality;
use CraftCms\Cms\Asset\Analysis\ImageAnalysisTask;

/**
 * Base class for AI tasks.
 *
 * ```php
 * class WriteMetaDescription extends Task
 * {
 *     public static function handle(): string
 *     {
 *         return 'seo:metaDescription';
 *     }
 *
 *     public static function label(): string
 *     {
 *         return t('Write meta descriptions', category: 'seo');
 *     }
 *
 *     public static function description(): ?string
 *     {
 *         return t('Writes search engine descriptions for entries.', category: 'seo');
 *     }
 *
 *     public static function requirements(): array
 *     {
 *         return [Capability::StructuredOutput];
 *     }
 * }
 * ```
 *
 * Tasks that analyze images, such as Craft’s own `craft:altText`, should extend
 * {@see ImageAnalysisTask} instead.
 *
 * @since 6.0.0
 */
abstract class Task implements TaskInterface
{
    public static function modality(): Modality
    {
        return Modality::Text;
    }

    public static function description(): ?string
    {
        return null;
    }

    public static function requirements(): array
    {
        return [];
    }
}
