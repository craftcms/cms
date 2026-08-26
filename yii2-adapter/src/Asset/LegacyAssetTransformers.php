<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Asset;

use Craft;
use CraftCms\Cms\Asset\AssetTransformers;
use Override;

class LegacyAssetTransformers extends AssetTransformers
{
    #[Override]
    protected function defaultImmediately(): bool
    {
        return Craft::$app->getConfig()->getGeneral()->generateTransformsBeforePageLoad;
    }
}
