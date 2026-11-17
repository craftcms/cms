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

        $generalConfig = Cms::config();

        if (!$generalConfig->cpIconPath && !$generalConfig->cpIconUrl && $rebrand->isIconUploaded()) {
            $generalConfig->cpIconPath = $rebrand->getIcon()->getPath();
        }

        if (!$generalConfig->cpLogoPath && !$generalConfig->cpLogoUrl && $rebrand->isLogoUploaded()) {
            $generalConfig->cpLogoPath = $rebrand->getLogo()->getPath();
        }
    }
}
