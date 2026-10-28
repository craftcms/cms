<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Analysis;

use CraftCms\Cms\Ai\AiTasks;
use CraftCms\Cms\Ai\Enums\Capability;
use CraftCms\Cms\Ai\Task;
use CraftCms\Cms\Asset\Analysis\Contracts\ImageAnalysisTaskInterface;
use CraftCms\Cms\Asset\Elements\Asset;

/**
 * Base class for image analysis tasks. Register them with {@see AiTasks} like other AI tasks.
 *
 * @since 6.0.0
 */
abstract class ImageAnalysisTask extends Task implements ImageAnalysisTaskInterface
{
    public static function requirements(): array
    {
        return [Capability::Vision, Capability::StructuredOutput];
    }

    public static function needed(Asset $asset, bool $overwrite): bool
    {
        return true;
    }
}
