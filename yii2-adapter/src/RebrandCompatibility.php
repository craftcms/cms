<?php

declare(strict_types=1);
namespace CraftCms\Yii2Adapter;

use craft\web\twig\variables\Rebrand;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\Deprecator;

class RebrandCompatibility
{
    public function boot(): void
    {
        if (!Edition::isAtLeast(Edition::Pro)) {
            return;
        }

        $rebrand = app(Rebrand::class);

        if (!Cms::config()->cpIconUrl && $rebrand->isIconUploaded()) {
            Deprecator::log('rebrand.iconUrl', 'Uploading rebrand assets is deprecated. Set `GeneralConfig->cpIconUrl()` instead.');

            Cms::config()->cpIconUrl = $rebrand->getIcon()->getUrl();
        }

        if (!Cms::config()->cpLogoUrl && $rebrand->isLogoUploaded()) {
            Deprecator::log('rebrand.logoUrl', 'Uploading rebrand assets is deprecated. Set `GeneralConfig->cpLogoUrl()` instead.');

            Cms::config()->cpLogoUrl = $rebrand->getLogo()->getUrl();
        }
    }
}
