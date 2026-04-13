<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\PreviewHandlers;

use CraftCms\Cms\Asset\Enums\FileKind;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Html;

use function CraftCms\Cms\template;

class Text extends AssetPreviewHandler
{
    public function getPreviewHtml(array $variables = []): string
    {
        $localCopy = $this->asset->getCopyOfFile();
        $contents = Html::encode(file_get_contents($localCopy));
        File::delete($localCopy);

        $language = $this->asset->kind === FileKind::Html->value ? 'markup' : $this->asset->kind;

        return template('assets/_previews/text', array_merge([
            'asset' => $this->asset,
            'language' => $language,
            'contents' => $contents,
        ], $variables));
    }
}
