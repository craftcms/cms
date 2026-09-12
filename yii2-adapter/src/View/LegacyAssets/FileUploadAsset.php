<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\View\LegacyAssets;

use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\LegacyAssets\JqueryUiAsset;
use CraftCms\Cms\View\LegacyAssets\LegacyAssetInterface;

use function CraftCms\Cms\craftAsset;

/**
 * @deprecated
 *
 * @internal
 */
class FileUploadAsset implements LegacyAssetInterface
{
    public array $depends = [
        JqueryUiAsset::class,
    ];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->jsFile(craftAsset('legacy/fileupload/dist/jquery.fileupload.js'));
    }
}
