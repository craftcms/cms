<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Analysis\Tasks;

use CraftCms\Cms\Asset\Analysis\Data\ImageAnalysis;
use CraftCms\Cms\Asset\Analysis\ImageAnalysisChanges;
use CraftCms\Cms\Asset\Analysis\ImageAnalysisTask;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\ImageType;
use Illuminate\Contracts\JsonSchema\JsonSchema;

use function CraftCms\Cms\t;

/**
 * Finds what kind of image an image is, such as a photo, a screenshot, or a diagram.
 *
 * @since 6.0.0
 */
class ClassifyImage extends ImageAnalysisTask
{
    public static function handle(): string
    {
        return 'craft:imageType';
    }

    public static function label(): string
    {
        return t('Classify images');
    }

    public static function description(): ?string
    {
        return t('Finds what kind of image each image is, such as a photo, an illustration, a screenshot, or a diagram, so they can be searched for by it.');
    }

    public static function needed(Asset $asset, bool $overwrite): bool
    {
        return $overwrite || $asset->analysis?->type === null;
    }

    public static function instructions(Asset $asset): string
    {
        $types = array_map(
            static fn (ImageType $type): string => "- $type->value: {$type->description()}",
            ImageType::cases(),
        );

        return implode("\n", [
            'Say what kind of image it is, choosing the one of these that fits best:',
            ...$types,
        ]);
    }

    public static function schema(JsonSchema $schema, Asset $asset): array
    {
        return [
            'type' => $schema->string()->enum(array_column(ImageType::cases(), 'value'))->required(),
        ];
    }

    public static function apply(Asset $asset, array $result, ImageAnalysisChanges $changes, bool $overwrite): void
    {
        // Builds on what other tasks have found in the image
        $analysis = $changes->get('analysis', $asset->analysis) ?? new ImageAnalysis;
        $type = is_string($result['type'] ?? null) ? ImageType::tryFrom($result['type']) : null;

        if ($type === null || (! $overwrite && $analysis->type !== null)) {
            return;
        }

        $changes->set('analysis', $analysis->withType($type));
    }
}
