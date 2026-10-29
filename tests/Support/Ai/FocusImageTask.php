<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support\Ai;

use CraftCms\Cms\Asset\Analysis\ImageAnalysisChanges;
use CraftCms\Cms\Asset\Analysis\ImageAnalysisTask;
use CraftCms\Cms\Asset\Elements\Asset;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class FocusImageTask extends ImageAnalysisTask
{
    public static function handle(): string
    {
        return 'test:focus';
    }

    public static function label(): string
    {
        return 'Focus';
    }

    public static function instructions(Asset $asset, bool $overwrite): string
    {
        return 'Find the subject.';
    }

    public static function schema(JsonSchema $schema, Asset $asset, bool $overwrite): array
    {
        return ['x' => $schema->number()->required(), 'y' => $schema->number()->required()];
    }

    public static function apply(Asset $asset, array $result, ImageAnalysisChanges $changes, bool $overwrite): void
    {
        $changes->set('focalPoint', ['x' => $result['x'], 'y' => $result['y']]);
    }
}
