<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Analysis\Contracts;

use CraftCms\Cms\Ai\Contracts\TaskInterface;
use CraftCms\Cms\Asset\Analysis\ImageAnalysisChanges;
use CraftCms\Cms\Asset\Analysis\ImageAnalysisTask;
use CraftCms\Cms\Asset\Elements\Asset;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;

/**
 * An AI task that learns something about an image, such as its alt text.
 *
 * Image analysis tasks that resolve to the same provider and model share one request: each adds its instructions to
 * the prompt and its properties to the response’s JSON schema, then applies its part of the response to the asset.
 *
 * Extend {@see ImageAnalysisTask} rather than implementing this directly.
 *
 * @since 6.0.0
 */
interface ImageAnalysisTaskInterface extends TaskInterface
{
    /**
     * Returns whether the task has anything to do for an image.
     *
     * @param  bool  $overwrite  Whether values the asset already has may be replaced, such as when an editor asks
     *                           for the task to run. Otherwise tasks should only fill in values the asset is missing.
     */
    public static function needed(Asset $asset, bool $overwrite): bool;

    /**
     * Returns what the model should do for the task, which is added to the prompt.
     */
    public static function instructions(Asset $asset): string;

    /**
     * Returns the properties of the task’s part of the response.
     *
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema, Asset $asset): array;

    /**
     * Applies the task’s part of the response to the asset, by recording changes to make.
     *
     * @param  array<string, mixed>  $result  The task’s part of the response, following {@see schema()}.
     */
    public static function apply(Asset $asset, array $result, ImageAnalysisChanges $changes, bool $overwrite): void;
}
