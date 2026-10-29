<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Analysis\Tasks;

use CraftCms\Cms\Asset\Analysis\ImageAnalysisChanges;
use CraftCms\Cms\Asset\Analysis\ImageAnalysisTask;
use CraftCms\Cms\Asset\Elements\Asset;
use Illuminate\Contracts\JsonSchema\JsonSchema;

use function CraftCms\Cms\t;

/**
 * Finds the point that crops of an image should keep in view, and sets it as the image’s focal point.
 *
 * @since 6.0.0
 */
class DetectFocalPoint extends ImageAnalysisTask
{
    public static function handle(): string
    {
        return 'craft:focalPoint';
    }

    public static function label(): string
    {
        return t('Detect focal points');
    }

    public static function description(): ?string
    {
        return t('Sets the focal points of images, so crops keep their subject in view, when they’re uploaded or analyzed.');
    }

    public static function needed(Asset $asset, bool $overwrite): bool
    {
        return $overwrite || ! $asset->getHasFocalPoint();
    }

    public static function instructions(Asset $asset, bool $overwrite): string
    {
        return 'Find the image’s focal point: the spot that crops of the image should keep in view. That’s usually the most important part of the main subject, such as a person’s face or eyes, or the center of the main object. For a scene without one subject, choose the center of what draws the eye. Give its position as fractions of the image’s width and height, measured from the top-left corner: x is 0 at the left edge and 1 at the right, and y is 0 at the top and 1 at the bottom.';
    }

    public static function schema(JsonSchema $schema, Asset $asset, bool $overwrite): array
    {
        return [
            'x' => $schema->number()->min(0)->max(1)->description('From 0 at the left edge to 1 at the right.')->required(),
            'y' => $schema->number()->min(0)->max(1)->description('From 0 at the top to 1 at the bottom.')->required(),
        ];
    }

    public static function apply(Asset $asset, array $result, ImageAnalysisChanges $changes, bool $overwrite): void
    {
        $x = $result['x'] ?? null;
        $y = $result['y'] ?? null;

        if (! is_numeric($x) || ! is_numeric($y) || (! $overwrite && $asset->getHasFocalPoint())) {
            return;
        }

        $changes->set('focalPoint', [
            'x' => max(0.0, min(1.0, (float) $x)),
            'y' => max(0.0, min(1.0, (float) $y)),
        ]);
    }
}
