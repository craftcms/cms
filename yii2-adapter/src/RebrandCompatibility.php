<?php

declare(strict_types=1);
namespace CraftCms\Yii2Adapter;

use craft\web\twig\variables\Rebrand;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;

class RebrandCompatibility
{
    public function boot(): void
    {
        if (!Edition::isAtLeast(Edition::Pro)) {
            return;
        }

        $rebrand = app(Rebrand::class);

        if (!Cms::config()->cpIconUrl && $rebrand->isIconUploaded()) {
            Cms::config()->cpIconUrl = $rebrand->getIcon()->getUrl();
        }

        if (!Cms::config()->cpLogoUrl && $rebrand->isLogoUploaded()) {
            Cms::config()->cpLogoUrl = $rebrand->getLogo()->getUrl();
        }
    }
}
