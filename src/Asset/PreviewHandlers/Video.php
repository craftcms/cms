<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\PreviewHandlers;

use CraftCms\Cms\Asset\Exceptions\AssetNotPreviewableException;

use function CraftCms\Cms\template;

class Video extends AssetPreviewHandler
{
    public function getPreviewHtml(array $variables = []): string
    {
        $url = $this->asset->getUrl();

        if ($url === null) {
            throw new AssetNotPreviewableException('Preview not supported.');
        }

        return template('assets/_previews/video', array_merge([
            'asset' => $this->asset,
            'url' => $url,
        ], $variables));
    }
}
