<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support\Ai;

use CraftCms\Cms\Asset\Analysis\ImageAnalysisChanges;
use CraftCms\Cms\Asset\Analysis\ImageAnalysisTask;
use CraftCms\Cms\Asset\Elements\Asset;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class CaptionImageTask extends ImageAnalysisTask
{
    public static function handle(): string
    {
        return 'test:caption';
    }

    public static function label(): string
    {
        return 'Caption';
    }

    public static function needed(Asset $asset, bool $overwrite): bool
    {
        return $overwrite || ($asset->alt ?? '') === '';
    }

    public static function instructions(Asset $asset, bool $overwrite): string
    {
        return 'Write a caption.';
    }

    public static function schema(JsonSchema $schema, Asset $asset, bool $overwrite): array
    {
        return ['caption' => $schema->string()->required()];
    }

    public static function apply(Asset $asset, array $result, ImageAnalysisChanges $changes, bool $overwrite): void
    {
        $changes->setForSite($asset->siteId, 'alt', $result['caption']);
    }
}
