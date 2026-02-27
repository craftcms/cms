<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\PreviewHandlers;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\Facades\File;

use function CraftCms\Cms\template;

class Text extends AssetPreviewHandler
{
    public function getPreviewHtml(array $variables = []): string
    {
        $localCopy = $this->asset->getCopyOfFile();
        $contents = Html::encode(file_get_contents($localCopy));
        File::delete($localCopy);

        $language = $this->asset->kind === Asset::KIND_HTML ? 'markup' : $this->asset->kind;

        return template('assets/_previews/text', array_merge([
            'asset' => $this->asset,
            'language' => $language,
            'contents' => $contents,
        ], $variables));
    }
}
