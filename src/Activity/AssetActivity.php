<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity;

use CraftCms\Cms\Activity\EventTypes\AssetFileReplaced;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Validation\AssetRules;
use CraftCms\Cms\Support\Facades\Activities;
use CraftCms\Cms\Support\Facades\Sites;
use LogicException;

/** @internal */
class AssetActivity
{
    public static function shouldRecord(Asset $asset, bool $recordActivity): bool
    {
        return $recordActivity &&
            ! $asset->propagating &&
            $asset->tempFilePath !== null &&
            $asset->ruleset->getScenario() === AssetRules::SCENARIO_REPLACE;
    }

    public static function original(Asset $asset): Asset
    {
        if ($asset->id === null) {
            throw new LogicException('Only existing asset files can be replaced.');
        }

        return Asset::find()->id($asset->id)->siteId($asset->siteId)->status(null)->one()
            ?? throw new LogicException("Could not load asset $asset->id before replacing its file.");
    }

    public static function recordReplaced(Asset $asset, Asset $original): void
    {
        Activities::record(new AssetFileReplaced(
            subject: $asset,
            site: Sites::getSiteById($asset->siteId),
            oldFilename: $original->getFilename(),
            newFilename: $asset->getFilename(),
            oldMimeType: $original->getMimeType(),
            newMimeType: $asset->getMimeType(),
            oldSize: $original->size,
            newSize: $asset->size,
        ));
    }
}
