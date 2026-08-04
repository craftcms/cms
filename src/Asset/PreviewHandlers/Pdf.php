<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\PreviewHandlers;

use CraftCms\Cms\Asset\Exceptions\AssetNotPreviewableException;
use CraftCms\Cms\Support\Html;

class Pdf extends AssetPreviewHandler
{
    public function getPreviewHtml(array $variables = []): string
    {
        $url = $this->asset->getUrl();

        if ($url === null) {
            throw new AssetNotPreviewableException('Preview not supported.');
        }

        return Html::tag('iframe', '', [
            'width' => '100%',
            'height' => '100%',
            'src' => $url,
        ]);
    }
}
