<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Analysis\Contracts;

use CraftCms\Cms\Ai\Contracts\TaskInterface;
use CraftCms\Cms\Asset\Analysis\ImageAnalysisChanges;
use CraftCms\Cms\Asset\Analysis\ImageAnalysisTask;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\Data\ImageMetadata;
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
     * Records what the metadata embedded in the image’s file fills in for the asset, such as alt text written for it,
     * in its own site or any of its others.
     *
     * This is called when an image is uploaded, and when its metadata is first read for analysis, before the tasks
     * that would run are chosen, whether or not AI is set up. Tasks should only fill in values the asset is missing,
     * so {@see needed()} sees them and the model isn’t asked for them.
     */
    public static function fillFromMetadata(Asset $asset, ImageMetadata $metadata, ImageAnalysisChanges $changes): void;

    /**
     * Returns whether the task has anything to do for an image.
     *
     * @param  bool  $overwrite  Whether values the asset already has may be replaced, such as when an editor asks
     *                           for the task to run. Otherwise tasks should only fill in values the asset is missing.
     */
    public static function needed(Asset $asset, bool $overwrite): bool;

    /**
     * Returns what the model should do for the task, which is added to the prompt.
     *
     * @param  bool  $overwrite  Whether values the asset already has may be replaced, as passed to {@see needed()}.
     */
    public static function instructions(Asset $asset, bool $overwrite): string;

    /**
     * Returns the properties of the task’s part of the response.
     *
     * @param  bool  $overwrite  Whether values the asset already has may be replaced, as passed to {@see needed()}.
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema, Asset $asset, bool $overwrite): array;

    /**
     * Applies the task’s part of the response to the asset, by recording changes to make.
     *
     * @param  array<string, mixed>  $result  The task’s part of the response, following {@see schema()}.
     */
    public static function apply(Asset $asset, array $result, ImageAnalysisChanges $changes, bool $overwrite): void;
}
