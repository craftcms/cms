<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\PreviewHandlers;

use craft\helpers\UrlHelper;
use CraftCms\Cms\Asset\Exceptions\AssetNotPreviewableException;
use CraftCms\Cms\Support\Facades\Assets;

use function CraftCms\Cms\template;

class Image extends AssetPreviewHandler
{
    public function getPreviewHtml(array $variables = []): string
    {
        try {
            $url = Assets::getImagePreviewUrl($this->asset, 1000, 1000);
        } catch (AssetNotPreviewableException) {
            $url = UrlHelper::actionUrl('assets/edit-image', [
                'assetId' => $this->asset->id,
                'size' => 1000,
            ]);
        }

        return template('assets/_previews/image', array_merge([
            'asset' => $this->asset,
            'url' => $url,
        ], $variables));
    }
}
