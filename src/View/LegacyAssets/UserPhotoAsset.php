<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\LegacyAssets;

use CraftCms\Cms\View\HtmlStack;

use function CraftCms\Cms\craftAsset;

/**
 * @deprecated
 *
 * @internal
 */
class UserPhotoAsset implements LegacyAssetInterface
{
    public array $depends = [
        CpAsset::class,
        FileUploadAsset::class,
    ];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->jsFile(craftAsset('legacy/userphoto/dist/UserPhotoInput.js'));
        $htmlStack->cssFile(craftAsset('legacy/userphoto/dist/css/UserPhotoInput.css'));
    }
}
